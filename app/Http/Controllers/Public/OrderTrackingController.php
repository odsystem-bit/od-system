<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\OrderDisputeMessage;
use App\Notifications\DisputeOpenedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * OrderTrackingController — Suivi de commande publique (Magic Link).
 *
 * Le client (sans compte) accede a sa commande via un lien unique
 * contenant un tracking_token. Il peut :
 *  - Consulter l'etat de sa commande.
 *  - Confirmer la reception (deblocage de l'escrow).
 *
 * Securite : aucune action n'est possible sans le bon token.
 */
class OrderTrackingController extends Controller
{
    // ──────────────────────────────────────────────
    //  Page de suivi (GET)
    // ──────────────────────────────────────────────

    /**
     * Affiche la page de suivi publique d'une commande.
     * Requiert le query parameter ?token=...
     */
    public function show(Request $request, Order $order): InertiaResponse
    {
        $this->authorizeToken($request, $order);

        $order->load([
            'product:id,name,image_path,price,type,access_url,digital_delivery_type,digital_file_path',
            'vendor:id,name,business_name,shop_name,slug,phone',
        ]);

        return Inertia::render('Shop/Track', [
            'order' => $order,
            'token' => $order->tracking_token,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Confirmation de reception → liberation escrow
    // ──────────────────────────────────────────────

    /**
     * Le client confirme la reception de sa commande.
     *
     * Transaction atomique :
     *  1. Verifie statut === shipped (anti-double-clic).
     *  2. Passe la commande en delivered.
     *  3. Libere l'escrow vendeur → balance disponible.
     *  4. Libere l'escrow créateur de contenu → balance disponible.
     */
    public function confirm(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeToken($request, $order);

        // Anti-double-validation : seul le statut 'shipped' permet la confirmation
        if ($order->status !== OrderStatus::SHIPPED) {
            return back()->withErrors([
                'order' => 'Cette commande ne peut pas etre confirmee. Elle doit etre au statut "Expediee".',
            ]);
        }

        // Verification du Code Secret de Livraison (OTP 4 chiffres)
        $request->validate([
            'pin' => ['required', 'string', 'size:4'],
        ], [
            'pin.required' => 'Le code de livraison est obligatoire.',
            'pin.size'     => 'Le code de livraison doit contenir 4 chiffres.',
        ]);

        if ($request->input('pin') !== $order->delivery_pin) {
            return back()->withErrors([
                'pin' => 'Code de livraison incorrect.',
            ]);
        }

        try {
        DB::transaction(function () use ($order): void {
            // 1. Recharger avec verrou pour eviter les race conditions
            $order = Order::where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Double-check apres verrouillage
            if ($order->status !== OrderStatus::SHIPPED) {
                return;
            }

            // 2. Passe la commande en livree
            $order->update(['status' => OrderStatus::DELIVERED]);

            // 3. Libere l'escrow vendeur → balance disponible
            $vendorWallet = Wallet::where('user_id', $order->vendor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $vendorEarnings = (float) $order->vendor_earnings;
            $vendorWallet->escrow_balance = max(0, (float) $vendorWallet->escrow_balance - $vendorEarnings);
            $vendorWallet->balance        = (float) $vendorWallet->balance + $vendorEarnings;
            $vendorWallet->save();

            // Transaction d'audit — revenu vendeur sur vente livree
            Transaction::create([
                'user_id'        => $order->vendor_id,
                'type'           => 'earning',
                'amount_target'  => $vendorEarnings,
                'gateway_fee'    => 0.00,
                'mantota_markup' => 0.00,
                'amount_total'   => $vendorEarnings,
                'status'         => 'completed',
                'reference'      => 'SALE-VENDOR-' . $order->reference,
                'description'    => 'Vente livree — Commande #' . $order->reference,
            ]);

            // 4. Libere l'escrow créateur de contenu → balance disponible (si applicable)
            if ($order->influencer_id && (float) $order->commission_amount > 0) {
                $influencerWallet = Wallet::where('user_id', $order->influencer_id)
                    ->lockForUpdate()
                    ->first();

                if ($influencerWallet) {
                    $commission = (float) $order->commission_amount;
                    $influencerWallet->escrow_balance = max(0, (float) $influencerWallet->escrow_balance - $commission);
                    $influencerWallet->balance        = (float) $influencerWallet->balance + $commission;
                    $influencerWallet->save();

                    // Transaction d'audit — commission CPA créateur de contenu
                    Transaction::create([
                        'user_id'        => $order->influencer_id,
                        'type'           => 'earning',
                        'amount_target'  => $commission,
                        'gateway_fee'    => 0.00,
                        'mantota_markup' => 0.00,
                        'amount_total'   => $commission,
                        'status'         => 'completed',
                        'reference'      => 'CPA-COMM-' . $order->reference,
                        'description'    => 'Commission CPA — Commande #' . $order->reference,
                    ]);
                }
            }
        });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Order confirm failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['order' => 'Une erreur est survenue lors de la confirmation. Veuillez reessayer.']);
        }

        \Illuminate\Support\Facades\Cache::forget('admin.dashboard');

        return back()->with('success', 'Reception confirmee ! Les fonds ont ete liberes au vendeur.');
    }

    // ──────────────────────────────────────────────
    //  Signalement d'un probleme (Dispute)
    // ──────────────────────────────────────────────

    /**
     * Le client signale un probleme sur sa commande.
     * Passe le statut en DISPUTED — les fonds restent en escrow.
     */
    public function dispute(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeToken($request, $order);

        if (! in_array($order->status, [OrderStatus::PENDING, OrderStatus::SHIPPED], true)) {
            return back()->withErrors([
                'order' => 'Le signalement n\'est pas possible pour cette commande.',
            ]);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Le motif est obligatoire.',
        ]);

        $order->update([
            'status'         => OrderStatus::DISPUTED,
            'dispute_reason' => $request->input('reason'),
        ]);

        // Notifier le vendeur
        $order->vendor?->notify(new DisputeOpenedNotification($order));

        // Notifier le bot Tracy pour alerte WhatsApp vendeur
        try {
            \Illuminate\Support\Facades\Http::withHeaders([
                'X-Bot-Api-Key' => config('services.bot.api_key'),
            ])->post(config('services.bot.internal_url') . '/api/internal/dispute-notify', [
                'vendor_id'       => $order->vendor_id,
                'order_reference' => $order->reference,
                'customer_name'   => $order->customer_name,
                'dispute_reason'  => $request->input('reason'),
            ]);
        } catch (\Throwable $e) {
            // Ne pas bloquer si le bot est inaccessible
            \Illuminate\Support\Facades\Log::warning(
                'Tracy bot dispute notify failed: ' . $e->getMessage()
            );
        }

        // Premier message automatique dans le chat de litige
        OrderDisputeMessage::create([
            'order_id'    => $order->id,
            'sender_type' => 'customer',
            'user_id'     => null,
            'message'     => $request->input('reason'),
        ]);

        return redirect()->route('public.dispute.chat', [
            'order' => $order->id,
            'token' => $order->tracking_token,
        ])->with('success', 'Signalement envoye. Vous pouvez echanger avec le vendeur et l\'equipe MANTOTA.');
    }

    // ──────────────────────────────────────────────
    //  Verification du token
    // ──────────────────────────────────────────────

    /**
     * Verifie que le tracking_token fourni en query string correspond a la commande.
     * Abort 403 si le token est invalide ou absent.
     */
    private function authorizeToken(Request $request, Order $order): void
    {
        $token = $request->query('token', $request->input('token'));

        if (! $token || ! $order->tracking_token || $token !== $order->tracking_token) {
            abort(403, 'Lien de suivi invalide ou expire.');
        }
    }

    // ──────────────────────────────────────────────
    //  Telechargement produit digital (fichier ZIP)
    // ──────────────────────────────────────────────

    /**
     * Telechargement securise du fichier ZIP du produit digital.
     * Requiert le token de suivi + commande payee/livree.
     */
    public function downloadDigital(Request $request, Order $order)
    {
        $this->authorizeToken($request, $order);

        // Seulement les commandes payees (digitales sont auto-delivered)
        if (! in_array($order->status, [OrderStatus::DELIVERED], true) || $order->payment_status !== 'paid') {
            abort(403, 'Cette commande n\'est pas encore payee.');
        }

        $product = $order->product;
        if (! $product || ! $product->isDigital() || $product->digital_delivery_type !== 'file' || ! $product->digital_file_path) {
            abort(404, 'Aucun fichier disponible pour ce produit.');
        }

        if (! Storage::disk('local')->exists($product->digital_file_path)) {
            abort(404, 'Le fichier n\'est plus disponible.');
        }

        $fileName = \Illuminate\Support\Str::slug($product->name) . '.zip';

        return Storage::disk('local')->download($product->digital_file_path, $fileName);
    }

    // ──────────────────────────────────────────────
    //  Retrouver ma commande (lookup public)
    // ──────────────────────────────────────────────

    /**
     * Affiche le formulaire "Retrouver ma commande".
     */
    public function lookup(): InertiaResponse
    {
        return Inertia::render('Shop/OrderLookup');
    }

    /**
     * Recherche une commande par (email + reference) ou (telephone + reference)
     * et redirige vers la page de suivi.
     */
    public function lookupSubmit(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'reference'  => ['required', 'string', 'max:50'],
        ], [
            'identifier.required' => 'L\'email ou le numero de telephone est obligatoire.',
            'reference.required'  => 'La reference de commande est obligatoire.',
        ]);

        $identifier = trim($request->input('identifier'));
        $reference  = trim($request->input('reference'));

        $order = Order::where('reference', $reference)
            ->where(function ($q) use ($identifier) {
                $q->where('customer_email', $identifier)
                  ->orWhere('customer_phone', $identifier);
            })
            ->first();

        if (! $order) {
            return back()->withErrors([
                'lookup' => 'Aucune commande trouvee avec ces informations. Verifiez votre email/telephone et votre reference (ex: CMD-XXXXXX).',
            ])->withInput();
        }

        return redirect()->route('order.track', [
            'order' => $order->id,
            'token' => $order->tracking_token,
        ]);
    }
}
