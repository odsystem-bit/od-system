<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\InfluencerService;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\UgcOrderNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ServiceOrderController extends Controller
{
    // ──────────────────────────────────────────────
    //  Catalogue des services (MANTOTA Studios)
    // ──────────────────────────────────────────────

    /**
     * Affiche le catalogue de tous les services disponibles
     * pour que le vendeur puisse parcourir avant de commander.
     */
    public function catalog(): InertiaResponse
    {
        $services = InfluencerService::with('influencer:id,name,profile_photo,country,tiktok_followers,instagram_followers,facebook_followers,youtube_followers,snapchat_followers')
            ->latest()
            ->paginate(20);

        return Inertia::render('Studios/Index', [
            'services' => $services,
            'ugc_studio_fee_percent' => (float) mantota_setting('ugc_studio_fee_percent', 15),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Liste des commandes du vendeur
    // ──────────────────────────────────────────────

    public function index(): InertiaResponse
    {
        $orders = ServiceOrder::where('vendor_id', auth()->id())
            ->with([
                'service:id,title,type,duration',
                'influencer:id,name',
                'product:id,name',
            ])
            ->latest()
            ->paginate(15);

        return Inertia::render('ServiceOrders/Index', [
            'orders' => $orders,
            'ugc_studio_fee_percent' => (float) mantota_setting('ugc_studio_fee_percent', 15),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Créer une commande (page formulaire)
    // ──────────────────────────────────────────────

    public function create(?int $serviceId = null): InertiaResponse
    {
        $service   = null;

        if ($serviceId) {
            $service = InfluencerService::with('influencer:id,name,profile_photo,country,tiktok_followers,instagram_followers,facebook_followers,youtube_followers,snapchat_followers')
                ->find($serviceId);
        }

        $products = auth()->user()->products()->select('id', 'name', 'type')->get();

        $wallet = Wallet::where('user_id', auth()->id())->first();

        return Inertia::render('ServiceOrders/Create', [
            'service'  => $service,
            'products' => $products,
            'wallet_balance' => (float) ($wallet->balance ?? 0),
            'ugc_studio_fee_percent' => (float) mantota_setting('ugc_studio_fee_percent', 15),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Passer commande — Escrow
    // ──────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:influencer_services,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'brief'      => ['required', 'string', 'max:5000'],
        ], [
            'service_id.required' => 'Le service est obligatoire.',
            'service_id.exists'   => 'Ce service n\'existe pas.',
            'brief.required'      => 'Le brief est obligatoire.',
            'brief.max'           => 'Le brief ne peut pas depasser 5000 caracteres.',
        ]);

        $vendorId = (int) auth()->id();
        $service  = InfluencerService::findOrFail($validated['service_id']);
        $amount   = (float) $service->price;

        // Verifier que le vendeur a les fonds suffisants
        $wallet = Wallet::where('user_id', $vendorId)->firstOrFail();

        if ((float) $wallet->balance < $amount) {
            return redirect()->back()->withErrors([
                'balance' => 'Solde insuffisant pour passer cette commande. Vous avez '
                    . number_format((float) $wallet->balance, 0, ',', ' ')
                    . ' FCFA, il faut ' . number_format($amount, 0, ',', ' ') . ' FCFA.',
            ]);
        }

        // ── Transaction atomique : debit vendeur → escrow (lockForUpdate) ──
        try {
            $serviceOrder = DB::transaction(function () use ($vendorId, $service, $amount, $validated) {
                /** @var Wallet $lockedWallet */
                $lockedWallet = Wallet::where('user_id', $vendorId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $lockedWallet->balance < $amount) {
                    throw new \RuntimeException('insufficient_balance');
                }

                // Debiter le vendeur
                $lockedWallet->decrement('balance', $amount);
                // Bloquer en escrow vendeur
                $lockedWallet->increment('escrow_balance', $amount);

                // Bloquer en escrow créateur de contenu (visibilite)
                $influencerWallet = Wallet::firstOrCreate(
                    ['user_id' => $service->influencer_id],
                    ['balance' => 0, 'pending_balance' => 0, 'escrow_balance' => 0]
                );
                Wallet::where('id', $influencerWallet->id)
                    ->lockForUpdate()
                    ->increment('escrow_balance', $amount);

                return ServiceOrder::create([
                    'vendor_id'          => $vendorId,
                    'influencer_id'      => $service->influencer_id,
                    'service_id'         => $service->id,
                    'product_id'         => $validated['product_id'] ?? null,
                    'amount'             => $amount,
                    'status'             => ServiceOrder::STATUS_PENDING,
                    'brief'              => $validated['brief'],
                    'revisions_allowed'  => (int) $service->included_revisions,
                    'sample_status'      => $this->resolveSampleStatus($validated['product_id'] ?? null),
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance') {
                return redirect()->back()->withErrors([
                    'balance' => 'Solde insuffisant. Veuillez recharger votre portefeuille.',
                ]);
            }
            throw $e;
        }

        // Notifier le créateur de contenu
        if ($serviceOrder && $influencer = User::find($service->influencer_id)) {
            $influencer->notify(new UgcOrderNotification($serviceOrder));
        }

        return redirect()
            ->route('vendor.service-orders.index')
            ->with('success', 'Commande passee. Le montant de ' . number_format($amount, 0, ',', ' ') . ' FCFA est bloque en escrow.');
    }

    // ──────────────────────────────────────────────
    //  Detail d'une commande (Show)
    // ──────────────────────────────────────────────

    public function show(ServiceOrder $order): InertiaResponse
    {
        $this->authorizeVendor($order);

        $order->load([
            'service:id,title,type,duration',
            'influencer:id,name,country,phone',
            'product:id,name,type',
            'product.images',
            'messages.sender:id,name,role',
        ]);

        return Inertia::render('ServiceOrders/Show', [
            'order'  => $order,
            'authId' => (int) auth()->id(),
            'ugc_studio_fee_percent' => (float) mantota_setting('ugc_studio_fee_percent', 15),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Approuver la livraison → paiement créateur de contenu
    //  Commission MANTOTA Studios (dynamique via settings)
    // ──────────────────────────────────────────────

    public function approve(ServiceOrder $order): RedirectResponse
    {
        $this->authorizeVendor($order);

        if ($order->status !== ServiceOrder::STATUS_DELIVERED) {
            return redirect()->back()->withErrors(['status' => 'Seule une commande livree peut etre approuvee.']);
        }

        DB::transaction(function () use ($order) {
            $vendorWallet = Wallet::where('user_id', $order->vendor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $influencerWallet = Wallet::firstOrCreate(
                ['user_id' => $order->influencer_id],
                ['balance' => 0, 'pending_balance' => 0, 'escrow_balance' => 0]
            );
            $influencerWallet = Wallet::where('id', $influencerWallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount     = (float) $order->amount;
            $feePercent = mantota_setting('ugc_studio_fee_percent', 15) / 100;
            $commission = round($amount * $feePercent, 2);
            $payout     = round($amount - $commission, 2);

            $vendorWallet->decrement('escrow_balance', $amount);
            $influencerWallet->decrement('escrow_balance', $amount);
            $influencerWallet->increment('balance', $payout);

            // Enregistrer la transaction UGC (earning créateur de contenu + commission MANTOTA)
            Transaction::create([
                'user_id'         => $order->influencer_id,
                'type'            => 'earning',
                'amount_target'   => $payout,
                'gateway_fee'     => 0,
                'mantota_markup'  => $commission,
                'amount_total'    => $amount,
                'status'          => 'completed',
                'reference'       => 'UGC-' . $order->id . '-' . now()->format('ymdHis'),
                'description'     => 'Paiement UGC - Commande #' . $order->id,
            ]);

            $order->update(['status' => ServiceOrder::STATUS_COMPLETED]);
        });

        return redirect()
            ->route('vendor.service-orders.show', $order)
            ->with('success', 'Video approuvee. Le createur de contenu a ete paye.');
    }

    // ──────────────────────────────────────────────
    //  Demander une retouche (1 seule autorisee)
    // ──────────────────────────────────────────────

    public function requestRevision(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorizeVendor($order);

        if ($order->status !== ServiceOrder::STATUS_DELIVERED) {
            return redirect()->back()->withErrors(['status' => 'Seule une commande livree peut faire l\'objet d\'une retouche.']);
        }

        if ($order->revisions_used >= $order->revisions_allowed) {
            return redirect()->back()->withErrors(['revision' => 'Vous avez deja utilise votre revision.']);
        }

        $validated = $request->validate([
            'revision_feedback' => ['required', 'string', 'max:2000'],
        ], [
            'revision_feedback.required' => 'Le motif de retouche est obligatoire.',
        ]);

        $order->update([
            'status'            => ServiceOrder::STATUS_REVISION_REQUESTED,
            'revision_feedback' => $validated['revision_feedback'],
            'revisions_used'    => $order->revisions_used + 1,
        ]);

        return redirect()
            ->route('vendor.service-orders.show', $order)
            ->with('success', 'Demande de retouche envoyee au createur de contenu.');
    }

    // ──────────────────────────────────────────────
    //  Ouvrir un litige (apres epuisement des retouches)
    // ──────────────────────────────────────────────

    public function dispute(ServiceOrder $order): RedirectResponse
    {
        $this->authorizeVendor($order);

        if ($order->status !== ServiceOrder::STATUS_DELIVERED) {
            return redirect()->back()->withErrors(['status' => 'Seule une commande livree peut faire l\'objet d\'un litige.']);
        }

        if ($order->revisions_used < $order->revisions_allowed) {
            return redirect()->back()->withErrors(['revision' => 'Vous devez d\'abord utiliser votre retouche avant d\'ouvrir un litige.']);
        }

        $order->update(['status' => ServiceOrder::STATUS_DISPUTED]);

        return redirect()
            ->route('vendor.service-orders.show', $order)
            ->with('success', 'Litige ouvert. L\'equipe MANTOTA va examiner le dossier.');
    }

    // ──────────────────────────────────────────────
    //  Rejeter la livraison → remboursement vendeur
    // ──────────────────────────────────────────────

    public function reject(ServiceOrder $order): RedirectResponse
    {
        $this->authorizeVendor($order);

        if ($order->status !== ServiceOrder::STATUS_DELIVERED) {
            return redirect()->back()->withErrors(['status' => 'Seule une commande livree peut etre rejetee.']);
        }

        DB::transaction(function () use ($order) {
            $vendorWallet = Wallet::where('user_id', $order->vendor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $vendorWallet->decrement('escrow_balance', (float) $order->amount);
            $vendorWallet->increment('balance', (float) $order->amount);

            // Reverser l'escrow créateur de contenu
            Wallet::where('user_id', $order->influencer_id)
                ->lockForUpdate()
                ->decrement('escrow_balance', (float) $order->amount);

            $order->update(['status' => ServiceOrder::STATUS_REJECTED]);
        });

        return redirect()
            ->route('vendor.service-orders.index')
            ->with('success', 'Video rejetee. Le montant a ete rembourse dans votre solde.');
    }

    // ──────────────────────────────────────────────
    //  Annulation par le vendeur (pending uniquement)
    //  Remboursement Escrow → Balance vendeur
    // ──────────────────────────────────────────────

    public function cancel(ServiceOrder $order): RedirectResponse
    {
        $this->authorizeVendor($order);

        if ($order->status !== ServiceOrder::STATUS_PENDING) {
            return redirect()->back()->withErrors(['status' => 'Seule une commande en attente peut etre annulee.']);
        }

        DB::transaction(function () use ($order): void {
            $lockedOrder = ServiceOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== ServiceOrder::STATUS_PENDING) {
                return;
            }

            $amount = (float) $lockedOrder->amount;

            $vendorWallet = Wallet::where('user_id', $lockedOrder->vendor_id)->lockForUpdate()->firstOrFail();
            $vendorWallet->escrow_balance = max(0, (float) $vendorWallet->escrow_balance - $amount);
            $vendorWallet->balance        = (float) $vendorWallet->balance + $amount;
            $vendorWallet->save();

            // Reverser l'escrow créateur de contenu
            $influencerWallet = Wallet::where('user_id', $lockedOrder->influencer_id)
                ->lockForUpdate()
                ->first();
            if ($influencerWallet) {
                $influencerWallet->escrow_balance = max(0, (float) $influencerWallet->escrow_balance - $amount);
                $influencerWallet->save();
            }

            $lockedOrder->update(['status' => ServiceOrder::STATUS_CANCELLED]);
        });

        return redirect()
            ->route('vendor.service-orders.index')
            ->with('success', 'Commande annulee. Le montant a ete rembourse dans votre solde.');
    }

    // ──────────────────────────────────────────────
    //  Marquer l'echantillon comme expedie
    // ──────────────────────────────────────────────

    public function markSampleShipped(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorizeVendor($order);

        if ($order->sample_status !== ServiceOrder::SAMPLE_PENDING_SHIPMENT) {
            return redirect()->back()->withErrors(['sample' => 'L\'echantillon ne peut pas etre expedie dans cet etat.']);
        }

        $validated = $request->validate([
            'delivery_name'  => ['required', 'string', 'max:255'],
            'delivery_phone' => ['required', 'string', 'max:50'],
        ], [
            'delivery_name.required'  => 'Le nom du livreur est obligatoire.',
            'delivery_phone.required' => 'Le telephone du livreur est obligatoire.',
        ]);

        $order->update([
            'sample_status'             => ServiceOrder::SAMPLE_SHIPPED,
            'sample_delivery_guy_name'  => $validated['delivery_name'],
            'sample_delivery_guy_phone' => $validated['delivery_phone'],
        ]);

        return redirect()
            ->route('vendor.service-orders.show', $order)
            ->with('success', 'Echantillon marque comme expedie.');
    }

    // ──────────────────────────────────────────────
    //  Guard
    // ──────────────────────────────────────────────

    private function authorizeVendor(ServiceOrder $order): void
    {
        abort_unless((int) $order->vendor_id === (int) auth()->id(), 403);
    }

    // ──────────────────────────────────────────────
    //  Helper : determine le sample_status initial
    // ──────────────────────────────────────────────

    private function resolveSampleStatus(?int $productId): string
    {
        if (! $productId) {
            return ServiceOrder::SAMPLE_NOT_REQUIRED;
        }

        $product = Product::find($productId);

        if ($product && $product->isPhysical()) {
            return ServiceOrder::SAMPLE_PENDING_SHIPMENT;
        }

        return ServiceOrder::SAMPLE_NOT_REQUIRED;
    }
}
