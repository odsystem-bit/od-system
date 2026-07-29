<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewSaleNotification;
use App\Services\Payment\FeexPayService;
use App\Services\Payment\GatewayResolver;
use App\Services\Payment\PayDunyaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * CheckoutController — Guest Checkout PayDunya / FeexPay + Escrow MANTOTA.
 *
 * Flux :
 *  1. Le client remplit ses coordonnees de livraison.
 *  2. On cree la commande (payment_status = 'awaiting'), on decremente le stock.
 *  3. On redirige vers la passerelle (PayDunya hosted page ou FeexPay USSD push).
 *  4. Le webhook confirme le paiement et credite l'escrow.
 *  5. Le client revient sur la page de succes.
 */
class CheckoutController extends Controller
{
    private const LOCATIONS = [
        'BJ' => [
            'name'  => 'Bénin',
            'cities' => ['Cotonou', 'Porto-Novo', 'Abomey-Calavi', 'Parakou', 'Bohicon', 'Autre...'],
        ],
        'CI' => [
            'name'  => 'Côte d\'Ivoire',
            'cities' => ['Abidjan', 'Bouaké', 'Daloa', 'Yamoussoukro', 'San-Pédro', 'Autre...'],
        ],
        'SN' => [
            'name'  => 'Sénégal',
            'cities' => ['Dakar', 'Thiès', 'Rufisque', 'Touba', 'Ziguinchor', 'Autre...'],
        ],
        'TG' => [
            'name'  => 'Togo',
            'cities' => ['Lomé', 'Sokodé', 'Kara', 'Kpalimé', 'Atakpamé', 'Autre...'],
        ],
        'CM' => [
            'name'  => 'Cameroun',
            'cities' => ['Douala', 'Yaoundé', 'Garoua', 'Bamenda', 'Maroua', 'Autre...'],
        ],
    ];

    private const FAST_DELIVERY_CITIES = ['Cotonou', 'Abomey-Calavi'];

    // ──────────────────────────────────────────────
    //  Formulaire de checkout (GET)
    // ──────────────────────────────────────────────

    public function show(Product $product): InertiaResponse
    {
        $product->load('vendor:id,name,business_name,shop_name,slug', 'images:id,product_id,path,sort_order');

        $targetCountry = null;
        if ($campaignId = session('campaign_id')) {
            $campaign = \App\Models\Campaign::find($campaignId);
            if ($campaign && $campaign->target_country) {
                $countries = is_array($campaign->target_country)
                    ? $campaign->target_country
                    : json_decode($campaign->target_country, true) ?? [];
                $targetCountry = is_string($countries) ? $countries : ($countries[0] ?? null);
            }
        }

        return Inertia::render('Shop/Checkout', [
            'product'         => $product,
            'target_country'  => $targetCountry,
            'influencer_id'   => session('partner_referrer'),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Traitement du paiement (POST)
    // ──────────────────────────────────────────────

    public function store(Request $request, Product $product)
    {
        // ── Validation du stock (produit physique) ──
        if ($product->isPhysical()) {
            if ($product->stock === null || $product->stock <= 0) {
                return back()->withErrors(['product' => 'Ce produit est en rupture de stock.']);
            }
        }

        // ── Regles de validation differenciees par type de produit ──
        if ($product->isDigital()) {
            $validated = $request->validate([
                'customer_name'      => ['required', 'string', 'max:255'],
                'customer_phone'     => ['required', 'string', 'max:30'],
                'customer_whatsapp'  => ['required', 'string', 'max:30'],
                'customer_email'     => ['required', 'email', 'max:255'],
            ], [
                'customer_name.required'       => 'Votre nom complet est obligatoire.',
                'customer_phone.required'      => 'Le numero de telephone est obligatoire.',
                'customer_whatsapp.required'   => 'Le numero WhatsApp est obligatoire.',
                'customer_email.required'      => 'L\'adresse email est obligatoire pour les produits digitaux.',
                'customer_email.email'         => 'Veuillez saisir une adresse email valide.',
            ]);

            $finalCity = null;
        } else {
            $validCountries = array_keys(self::LOCATIONS);
            $validCities = [];
            foreach (self::LOCATIONS as $countryData) {
                $validCities = array_merge($validCities, $countryData['cities']);
            }
            $validCities = array_unique($validCities);

            $validated = $request->validate([
                'customer_name'      => ['required', 'string', 'max:255'],
                'customer_phone'     => ['required', 'string', 'max:30'],
                'customer_whatsapp'  => ['required', 'string', 'max:30'],
                'country'            => ['required', 'string', Rule::in($validCountries)],
                'city'               => ['required', 'string', Rule::in($validCities)],
                'custom_city'        => ['nullable', 'string', 'max:255'],
                'landmark_indication' => ['required', 'string', 'max:1000'],
            ], [
                'customer_name.required'       => 'Votre nom complet est obligatoire.',
                'customer_phone.required'      => 'Le numero de telephone est obligatoire.',
                'customer_whatsapp.required'   => 'Le numero WhatsApp est obligatoire pour le suivi de livraison.',
                'country.required'             => 'Veuillez selectionner un pays.',
                'country.in'                   => 'Pays non supporte.',
                'city.required'                => 'Veuillez selectionner une ville.',
                'city.in'                      => 'Ville non reconnue.',
                'landmark_indication.required' => 'Le quartier et le repere precis sont obligatoires pour la livraison.',
            ]);

            // ── Verrouillage pays campagne ──
            if ($campaignId = session('campaign_id')) {
                $campaign = \App\Models\Campaign::find($campaignId);
                if ($campaign && $campaign->target_country) {
                    $countries = is_array($campaign->target_country)
                        ? $campaign->target_country
                        : (json_decode($campaign->target_country, true) ?? []);
                    $locked = is_string($countries) ? $countries : ($countries[0] ?? null);
                    if ($locked && $validated['country'] !== $locked) {
                        return back()->withErrors([
                            'country' => 'Cette campagne est reservee au pays : ' . $locked . '.',
                        ]);
                    }
                }
            }

            // ── Filet de Securite "Autre..." ──
            if ($validated['city'] === 'Autre...') {
                if (!$validated['custom_city']) {
                    return back()->withErrors([
                        'custom_city' => 'Precisez votre ville ou quartier si vous choisissez "Autre..."'
                    ]);
                }
                $finalCity = $validated['custom_city'];
            } else {
                $finalCity = $validated['city'];
            }
        }

        // ── Calcul du split financier ──
        $productPrice     = (float) $product->price;
        $commissionRate   = (float) $product->commission_percent;
        $influencerId     = $request->session()->get('partner_referrer');
        $commissionAmount = $influencerId ? round($productPrice * ($commissionRate / 100), 2) : 0.00;

        $deliveryFeePaid = 0.00;
        if ($product->isPhysical() && $product->delivery_type === 'fixed' && $product->delivery_fee) {
            $deliveryFeePaid = (float) $product->delivery_fee;
        }

        $vendorEarnings = round($productPrice - $commissionAmount + $deliveryFeePaid, 2);
        $amountPaid = round($productPrice + $deliveryFeePaid, 2);

        $deliveryHours = $product->isPhysical() && $finalCity
            ? (in_array($finalCity, self::FAST_DELIVERY_CITIES) ? 24 : 72)
            : null;

        // ── Cree la commande atomiquement (payment_status = awaiting) ──
        $order = null;

        try {
            $order = DB::transaction(function () use (
                $product, $validated, $finalCity, $amountPaid, $commissionAmount, $vendorEarnings,
                $deliveryFeePaid, $influencerId, $deliveryHours
            ) {
                // Verrouiller le produit pour eviter l'oversell
                if ($product->isPhysical()) {
                    $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first();
                    if (!$lockedProduct || $lockedProduct->stock === null || $lockedProduct->stock <= 0) {
                        throw new \RuntimeException('stock_exhausted');
                    }
                    $lockedProduct->decrement('stock');
                }

                return Order::create([
                    'reference'           => Order::generateReference(),
                    'vendor_id'           => $product->vendor_id,
                    'product_id'          => $product->id,
                    'influencer_id'       => $influencerId,
                    'campaign_id'         => session('campaign_id'),
                    'customer_name'       => $validated['customer_name'],
                    'customer_phone'      => $validated['customer_phone'],
                    'customer_whatsapp'   => $validated['customer_whatsapp'],
                    'customer_email'      => $validated['customer_email'] ?? null,
                    'city'                => $finalCity,
                    'country'             => $validated['country'] ?? null,
                    'landmark_indication' => $validated['landmark_indication'] ?? null,
                    'amount_paid'         => $amountPaid,
                    'commission_amount'   => $commissionAmount,
                    'vendor_earnings'     => $vendorEarnings,
                    'delivery_fee_paid'   => $deliveryFeePaid,
                    'status'              => OrderStatus::PENDING,
                    'payment_status'      => 'awaiting',
                    'delivery_deadline'   => $deliveryHours ? now()->addHours($deliveryHours) : null,
                    'tracking_token'      => Str::random(64),
                    'delivery_pin'        => $product->isPhysical() ? str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT) : null,
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'stock_exhausted') {
                return back()->withErrors(['product' => 'Ce produit est en rupture de stock.']);
            }
            throw $e;
        }

        // ── Initier le paiement via la passerelle active ──
        $customerCountry = $validated['country'] ?? null;
        $gateway = GatewayResolver::resolve($customerCountry);

        if (! $gateway) {
            return $this->restoreStockAndFail($order, 'Aucune passerelle de paiement active. Contactez l\'administration.');
        }

        return match ($gateway->slug) {
            'paydunya' => $this->initiatePayDunya($order),
            'feexpay'  => $this->initiateFeexPay($order, $request),
            default    => $this->restoreStockAndFail($order, 'Passerelle de paiement non supportee.'),
        };
    }

    // ──────────────────────────────────────────────
    //  Initiation PayDunya (hosted checkout page)
    // ──────────────────────────────────────────────

    private function initiatePayDunya(Order $order)
    {
        $config = PayDunyaService::resolveApiConfig();

        if (! $config || ! $config['masterKey'] || ! $config['privateKey'] || ! $config['token']) {
            return $this->restoreStockAndFail($order, 'Configuration PayDunya incomplete. Contactez l\'administration.');
        }

        try {
            $response = Http::withHeaders(PayDunyaService::apiHeaders($config['masterKey'], $config['privateKey'], $config['token']))
                ->connectTimeout(10)
                ->timeout(30)
                ->post("{$config['apiBase']}/api/v1/checkout-invoice/create", [
                    'invoice' => [
                        'total_amount' => (int) $order->amount_paid,
                        'description'  => 'Paiement commande ' . $order->reference,
                    ],
                    'store' => [
                        'name' => (string) mantota_setting('company_name', 'MANTOTA'),
                    ],
                    'custom_data' => [
                        'order_id' => $order->id,
                    ],
                    'actions' => [
                        'callback_url' => route('webhooks.paydunya'),
                        'return_url'   => route('shop.checkout.payment-return', [
                            'order' => $order->id,
                            'token' => $order->tracking_token,
                        ]),
                        'cancel_url' => route('shop.checkout.show', $order->product_id),
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PayDunya checkout: timeout/connexion', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return $this->restoreStockAndFail($order, 'La passerelle de paiement ne repond pas. Veuillez reessayer.');
        }

        if ($response->failed() || $response->json('response_code') !== '00') {
            Log::error('PayDunya checkout: erreur API', [
                'status'   => $response->status(),
                'body'     => $response->body(),
                'order_id' => $order->id,
            ]);
            return $this->restoreStockAndFail($order, 'Erreur passerelle de paiement. Veuillez reessayer.');
        }

        $paymentUrl = $response->json('response_text');
        $pdyToken   = $response->json('token', '');

        if (! $paymentUrl) {
            return $this->restoreStockAndFail($order, 'Lien de paiement introuvable.');
        }

        $order->update([
            'payment_gateway_ref' => $pdyToken,
            'payment_gateway'     => 'paydunya',
        ]);

        Log::info('PayDunya checkout: redirection vers paiement', [
            'order_id'  => $order->id,
            'reference' => $order->reference,
            'amount'    => $order->amount_paid,
        ]);

        return Inertia::location($paymentUrl);
    }

    // ──────────────────────────────────────────────
    //  Initiation FeexPay (USSD push → polling)
    // ──────────────────────────────────────────────

    private function initiateFeexPay(Order $order, Request $request)
    {
        $phone = $request->input('customer_phone', $order->customer_phone ?? '');
        $checkoutCountry = $order->country ?? $request->input('country') ?? 'BJ';
        $network = FeexPayService::detectNetwork($phone, $checkoutCountry);

        if (! $network || ! $phone) {
            // Essayer avec le whatsapp
            $phone = $request->input('customer_whatsapp', $order->customer_whatsapp ?? '');
            $network = FeexPayService::detectNetwork($phone, $checkoutCountry);
        }

        if (! $network || ! $phone) {
            return $this->restoreStockAndFail($order, 'Numero de telephone non reconnu pour le paiement mobile. Assurez-vous d\'utiliser un numero MTN ou MOOV.');
        }

        $result = FeexPayService::initiateMobilePayment(
            (int) $order->amount_paid,
            $phone,
            $network,
            $order->customer_name ?? 'Client',
            $order->customer_email ?? ''
        );

        if (! $result['success']) {
            Log::error('FeexPay checkout: erreur initiation', [
                'order_id' => $order->id,
                'message'  => $result['message'],
            ]);
            return $this->restoreStockAndFail($order, 'Erreur paiement mobile: ' . $result['message']);
        }

        $order->update([
            'payment_gateway_ref' => $result['reference'],
            'payment_gateway'     => 'feexpay',
        ]);

        Log::info('FeexPay checkout: USSD push envoye', [
            'order_id'  => $order->id,
            'reference' => $result['reference'],
            'network'   => $network,
            'amount'    => $order->amount_paid,
        ]);

        // Rediriger vers la page de polling (le client approuve sur son telephone)
        return redirect()->route('shop.checkout.payment-return', [
            'order' => $order->id,
            'token' => $order->tracking_token,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Restaurer le stock si FedaPay echoue
    // ──────────────────────────────────────────────

    private function restoreStockAndFail(Order $order, string $errorMessage): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $order->update(['payment_status' => 'failed']);

            $product = Product::where('id', $order->product_id)->lockForUpdate()->first();
            if ($product && $product->isPhysical() && $product->stock !== null) {
                $product->increment('stock');
            }
        });

        return redirect()->route('shop.checkout.show', $order->product_id)
            ->withErrors(['payment' => $errorMessage]);
    }

    // ──────────────────────────────────────────────
    //  Retour apres paiement FedaPay (GET)
    // ──────────────────────────────────────────────

    public function paymentReturn(Request $request, Order $order): InertiaResponse|RedirectResponse
    {
        if ($request->query('token') !== $order->tracking_token) {
            abort(403, 'Lien invalide.');
        }

        // Si le webhook a deja confirme le paiement
        if ($order->payment_status === 'paid') {
            return redirect()->route('shop.checkout.success', ['order' => $order, 'token' => $order->tracking_token]);
        }

        // Verifier activement aupres de la passerelle
        if ($order->payment_gateway_ref && $order->payment_status === 'awaiting') {
            $credited = self::verifyAndCreditOrder($order);
            if ($credited) {
                return redirect()->route('shop.checkout.success', ['order' => $order, 'token' => $order->tracking_token]);
            }
        }

        // Page de traitement avec polling
        return Inertia::render('Shop/PaymentProcessing', [
            'order_id'    => $order->id,
            'reference'   => $order->reference,
            'amount_paid' => $order->amount_paid,
            'token'       => $order->tracking_token,
            'status_url'  => route('shop.checkout.payment-status', $order),
            'success_url' => route('shop.checkout.success', ['order' => $order, 'token' => $order->tracking_token]),
            'cancel_url'  => route('shop.checkout.show', $order->product_id),
        ]);
    }

    // ──────────────────────────────────────────────
    //  API de polling du statut (JSON)
    // ──────────────────────────────────────────────

    public function checkPaymentStatus(Request $request, Order $order): JsonResponse
    {
        if ($request->query('token') !== $order->tracking_token) {
            return response()->json(['payment_status' => 'unauthorized'], 403);
        }

        // Verifier activement aupres de la passerelle si encore en attente
        if ($order->payment_status === 'awaiting' && $order->payment_gateway_ref) {
            $credited = self::verifyAndCreditOrder($order);
            if ($credited) {
                return response()->json(['payment_status' => 'paid']);
            }
            $order->refresh();
        }

        return response()->json(['payment_status' => $order->payment_status]);
    }

    // ──────────────────────────────────────────────
    //  Page de succes post-paiement (GET)
    // ──────────────────────────────────────────────

    public function success(Request $request, Order $order): InertiaResponse|RedirectResponse
    {
        if ($request->query('token') !== $order->tracking_token || $order->payment_status !== 'paid') {
            return redirect()->route('shop.checkout.show', $order->product_id);
        }

        $order->load([
            'product:id,name,image_path,price,type,access_url,digital_delivery_type,digital_file_path',
            'vendor:id,name,business_name,shop_name,slug,phone',
        ]);

        if ($order->delivery_pin) {
            $order->makeVisible('delivery_pin');
        }

        return Inertia::render('Shop/Success', [
            'order' => $order,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Verification active multi-passerelle
    // ──────────────────────────────────────────────

    public static function verifyAndCreditOrder(Order $order): bool
    {
        if ($order->payment_status !== 'awaiting' || ! $order->payment_gateway_ref) {
            return false;
        }

        return match ($order->payment_gateway) {
            'paydunya' => self::verifyAndCreditOrderPayDunya($order),
            'feexpay'  => self::verifyAndCreditOrderFeexPay($order),
            default    => false,
        };
    }

    // ──────────────────────────────────────────────
    //  Verification PayDunya via API Confirm
    // ──────────────────────────────────────────────

    private static function verifyAndCreditOrderPayDunya(Order $order): bool
    {
        try {
            $status = PayDunyaService::checkPaymentStatus($order->payment_gateway_ref);

            if ($status !== 'completed') {
                if (in_array($status, ['cancelled', 'failed'], true)) {
                    $order->update(['payment_status' => 'failed']);
                    $product = Product::find($order->product_id);
                    if ($product && $product->isPhysical() && $product->stock !== null) {
                        $product->increment('stock');
                    }
                }
                return false;
            }

            self::creditEscrowAndNotify($order);
            return true;
        } catch (\Throwable $e) {
            Log::error('PayDunya order verification error', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ──────────────────────────────────────────────
    //  Verification FeexPay via API status
    // ──────────────────────────────────────────────

    private static function verifyAndCreditOrderFeexPay(Order $order): bool
    {
        try {
            $result = FeexPayService::checkPaymentStatus($order->payment_gateway_ref);
            $status = $result['status'] ?? null;

            if ($status !== 'SUCCESSFUL') {
                if ($status === 'FAILED') {
                    $order->update(['payment_status' => 'failed']);
                    $product = Product::find($order->product_id);
                    if ($product && $product->isPhysical() && $product->stock !== null) {
                        $product->increment('stock');
                    }
                }
                return false;
            }

            self::creditEscrowAndNotify($order);
            return true;
        } catch (\Throwable $e) {
            Log::error('FeexPay order verification error', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ──────────────────────────────────────────────
    //  Credit escrow + notifications (commun)
    // ──────────────────────────────────────────────

    private static function creditEscrowAndNotify(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_status !== 'awaiting') {
                return;
            }

            // Bloquer les gains vendeur en escrow
            $vendorWallet = Wallet::where('user_id', $lockedOrder->vendor_id)
                ->lockForUpdate()
                ->firstOrFail();
            $vendorWallet->escrow_balance = (float) $vendorWallet->escrow_balance + (float) $lockedOrder->vendor_earnings;
            $vendorWallet->save();

            // Bloquer la commission créateur de contenu en escrow
            if ($lockedOrder->influencer_id && (float) $lockedOrder->commission_amount > 0) {
                $influencerWallet = Wallet::where('user_id', $lockedOrder->influencer_id)
                    ->lockForUpdate()
                    ->first();
                if ($influencerWallet) {
                    $influencerWallet->escrow_balance = (float) $influencerWallet->escrow_balance + (float) $lockedOrder->commission_amount;
                    $influencerWallet->save();
                }
            }

            $lockedOrder->update(['payment_status' => 'paid']);
        });

        $order->refresh();

        // Produit digital : livraison automatique
        self::autoDeliverDigital($order);

        // Notifications
        if ($vendor = User::find($order->vendor_id)) {
            $vendor->notify(new NewSaleNotification($order, 'vendor'));
        }
        if ($order->influencer_id && $influencer = User::find($order->influencer_id)) {
            $influencer->notify(new NewSaleNotification($order, 'influencer'));
        }

        Cache::forget('admin.dashboard');

        Log::info('Checkout: paiement commande confirme', [
            'order_id'  => $order->id,
            'reference' => $order->reference,
            'gateway'   => $order->payment_gateway,
            'amount'    => $order->amount_paid,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Auto-livraison produit digital
    // ──────────────────────────────────────────────

    public static function autoDeliverDigital(Order $order): void
    {
        $product = $order->product ?? Product::find($order->product_id);
        if (! $product || ! $product->isDigital()) {
            return;
        }

        if ($order->status !== OrderStatus::PENDING || $order->payment_status !== 'paid') {
            return;
        }

        try {
            DB::transaction(function () use ($order): void {
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->status !== OrderStatus::PENDING || $lockedOrder->payment_status !== 'paid') {
                    return;
                }

                $lockedOrder->update(['status' => OrderStatus::DELIVERED]);

                // Liberer escrow vendeur → balance
                $vendorWallet = Wallet::where('user_id', $lockedOrder->vendor_id)->lockForUpdate()->firstOrFail();
                $vendorEarnings = (float) $lockedOrder->vendor_earnings;
                $vendorWallet->escrow_balance = max(0, (float) $vendorWallet->escrow_balance - $vendorEarnings);
                $vendorWallet->balance = (float) $vendorWallet->balance + $vendorEarnings;
                $vendorWallet->save();

                \App\Models\Transaction::create([
                    'user_id'        => $lockedOrder->vendor_id,
                    'type'           => 'earning',
                    'amount_target'  => $vendorEarnings,
                    'gateway_fee'    => 0.00,
                    'mantota_markup' => 0.00,
                    'amount_total'   => $vendorEarnings,
                    'status'         => 'completed',
                    'reference'      => 'DIGITAL-VENDOR-' . $lockedOrder->reference,
                    'description'    => 'Vente digitale auto-livree — #' . $lockedOrder->reference,
                ]);

                // Liberer escrow créateur de contenu → balance
                if ($lockedOrder->influencer_id && (float) $lockedOrder->commission_amount > 0) {
                    $influencerWallet = Wallet::where('user_id', $lockedOrder->influencer_id)->lockForUpdate()->first();
                    if ($influencerWallet) {
                        $commission = (float) $lockedOrder->commission_amount;
                        $influencerWallet->escrow_balance = max(0, (float) $influencerWallet->escrow_balance - $commission);
                        $influencerWallet->balance = (float) $influencerWallet->balance + $commission;
                        $influencerWallet->save();

                        \App\Models\Transaction::create([
                            'user_id'        => $lockedOrder->influencer_id,
                            'type'           => 'earning',
                            'amount_target'  => $commission,
                            'gateway_fee'    => 0.00,
                            'mantota_markup' => 0.00,
                            'amount_total'   => $commission,
                            'status'         => 'completed',
                            'reference'      => 'DIGITAL-COMM-' . $lockedOrder->reference,
                            'description'    => 'Commission digitale auto-livree — #' . $lockedOrder->reference,
                        ]);
                    }
                }
            });

            $order->refresh();
            Log::info('Digital auto-delivery done', ['order_id' => $order->id, 'reference' => $order->reference]);

            // Envoyer le produit digital par email au client
            if ($order->customer_email) {
                $product = $order->product;
                if ($product && $product->digital_delivery_type === 'file' && $product->digital_file_path) {
                    // Mode fichier : envoyer un lien de telechargement securise
                    $downloadUrl = route('order.track.download', [
                        'order' => $order->id,
                        'token' => $order->tracking_token,
                    ]);
                    \Illuminate\Support\Facades\Notification::route('mail', $order->customer_email)
                        ->notify(new \App\Notifications\DigitalProductDeliveredNotification($order, $downloadUrl, true));
                } elseif ($product && $product->access_url) {
                    // Mode lien : envoyer l'URL externe
                    \Illuminate\Support\Facades\Notification::route('mail', $order->customer_email)
                        ->notify(new \App\Notifications\DigitalProductDeliveredNotification($order, $product->access_url));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Digital auto-delivery failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
