<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Enums\OrderStatus;
use App\Events\NewDisputeMessage;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDisputeMessage;
use App\Services\ChatModeratorService;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Payment\FedaPayService;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * OrderController — Gestion des commandes e-commerce (cote vendeur).
 *
 * Responsabilites :
 *  - Lister les commandes avec infos livraison et countdown.
 *  - Permettre au vendeur de marquer une commande comme "Expediee".
 */
class OrderController extends Controller
{
    // ──────────────────────────────────────────────
    //  Liste des commandes
    // ──────────────────────────────────────────────

    /**
     * Affiche la liste des commandes du vendeur connecte.
     */
    public function index(): InertiaResponse
    {
        $vendorId = (int) auth()->id();

        $orders = Order::query()
            ->where('vendor_id', $vendorId)
            ->where('payment_status', 'paid')
            ->with(['product:id,name,image_path,price', 'influencer:id,name'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Detail d'une commande
    // ──────────────────────────────────────────────

    public function show(Order $order): InertiaResponse
    {
        if ((int) $order->vendor_id !== (int) auth()->id()) {
            abort(403);
        }

        $order->load(['product:id,name,image_path,price', 'influencer:id,name']);

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Marquer comme expediee
    // ──────────────────────────────────────────────

    /**
     * Passe la commande de 'pending' à 'shipped'.
     * Le vendeur doit fournir le nom et le telephone du livreur.
     */
    public function markShipped(Request $request, Order $order): RedirectResponse
    {
        // Verifier que la commande appartient au vendeur
        if ((int) $order->vendor_id !== (int) auth()->id()) {
            abort(403);
        }

        if ($order->status !== OrderStatus::PENDING) {
            return back()->withErrors([
                'order' => 'Cette commande ne peut pas etre marquee comme expediee.',
            ]);
        }

        if ($order->payment_status !== 'paid') {
            return back()->withErrors([
                'order' => 'Le paiement de cette commande n\'a pas encore ete confirme.',
            ]);
        }

        $validated = $request->validate([
            'delivery_guy_name'  => ['required', 'string', 'max:255'],
            'delivery_guy_phone' => ['required', 'string', 'max:30'],
            'delivery_company'   => ['required', 'string', Rule::in(['Gozem', 'Yango', 'Rema', 'Kaba', 'Autre'])],
            'vendor_shipping_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'delivery_guy_name.required'  => 'Le nom du livreur est obligatoire.',
            'delivery_guy_phone.required' => 'Le telephone du livreur est obligatoire.',
            'delivery_company.required'   => 'Veuillez selectionner une societe de livraison.',
            'delivery_company.in'         => 'Societe de livraison non reconnue.',
            'vendor_shipping_note.max'    => 'Le message ne doit pas depasser 1000 caracteres.',
        ]);

        $order->update([
            'status'             => OrderStatus::SHIPPED,
            'delivery_guy_name'  => $validated['delivery_guy_name'],
            'delivery_guy_phone' => $validated['delivery_guy_phone'],
            'delivery_company'   => $validated['delivery_company'],
            'vendor_shipping_note' => $validated['vendor_shipping_note'] ?? null,
        ]);

        return back()->with('success', 'Commande ' . $order->reference . ' marquee comme expediee.');
    }

    // ──────────────────────────────────────────────
    //  Annulation commande (Vendeur)
    // ──────────────────────────────────────────────

    /**
     * Le vendeur annule une commande.
     * Si le paiement est confirmé (payment_status = paid) :
     *  - Reverse l'escrow vendeur et créateur de contenu.
     *  - Restaure le stock (physique).
     *  - Marque payment_status = 'refunded'.
     * Le remboursement réel vers le client doit être traité manuellement
     * via la passerelle de paiement ou par l'administration.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        // Vérifier que la commande appartient au vendeur connecté
        if ((int) $order->vendor_id !== (int) auth()->id()) {
            abort(403);
        }

        // Seules les commandes PENDING ou SHIPPED peuvent être annulées
        if (! in_array($order->status, [OrderStatus::PENDING, OrderStatus::SHIPPED], true)) {
            return back()->withErrors([
                'order' => 'Cette commande ne peut plus être annulée (statut : ' . $order->status->label() . ').',
            ]);
        }

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:1000'],
        ], [
            'cancel_reason.required' => 'Veuillez indiquer la raison de l\'annulation.',
            'cancel_reason.max'      => 'La raison ne doit pas dépasser 1000 caractères.',
        ]);

        try {
            DB::transaction(function () use ($order, $validated): void {
                $order = Order::with('product')
                    ->where('id', $order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            // Re-vérifier le statut après verrouillage
            if (! in_array($order->status, [OrderStatus::PENDING, OrderStatus::SHIPPED], true)) {
                return;
            }

            // ── Reverser l'escrow si le paiement avait été confirmé ──
            if ($order->payment_status === 'paid') {
                // Retirer le gain vendeur de son escrow
                $vendorWallet = Wallet::where('user_id', $order->vendor_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $vendorWallet->escrow_balance = max(0, (float) $vendorWallet->escrow_balance - (float) $order->vendor_earnings);
                $vendorWallet->save();

                // Retirer la commission créateur de contenu de son escrow
                if ($order->influencer_id && (float) $order->commission_amount > 0) {
                    $influencerWallet = Wallet::where('user_id', $order->influencer_id)
                        ->lockForUpdate()
                        ->first();
                    if ($influencerWallet) {
                        $influencerWallet->escrow_balance = max(0, (float) $influencerWallet->escrow_balance - (float) $order->commission_amount);
                        $influencerWallet->save();
                    }
                }

                // Restaurer le stock produit physique
                if ($order->product && $order->product->isPhysical() && $order->product->stock !== null) {
                    $order->product->increment('stock');
                }

                // Audit trail — reversal escrow vendeur
                Transaction::create([
                    'user_id'        => $order->vendor_id,
                    'type'           => 'fee',
                    'amount_target'  => (float) $order->vendor_earnings,
                    'gateway_fee'    => 0.00,
                    'mantota_markup' => 0.00,
                    'amount_total'   => (float) $order->vendor_earnings,
                    'status'         => 'completed',
                    'reference'      => 'CANCEL-VENDOR-' . $order->reference,
                    'description'    => 'Annulation commande — Escrow reverse #' . $order->reference,
                ]);

                // Audit trail — reversal escrow créateur de contenu
                if ($order->influencer_id && (float) $order->commission_amount > 0) {
                    Transaction::create([
                        'user_id'        => $order->influencer_id,
                        'type'           => 'fee',
                        'amount_target'  => (float) $order->commission_amount,
                        'gateway_fee'    => 0.00,
                        'mantota_markup' => 0.00,
                        'amount_total'   => (float) $order->commission_amount,
                        'status'         => 'completed',
                        'reference'      => 'CANCEL-COMM-' . $order->reference,
                        'description'    => 'Annulation commande — Commission reverse #' . $order->reference,
                    ]);
                }
            }

            $order->update([
                'status'         => OrderStatus::CANCELLED,
                'payment_status' => $order->payment_status === 'paid' ? 'refunded' : $order->payment_status,
                'cancel_reason'  => $validated['cancel_reason'],
            ]);
        });
        } catch (\Throwable $e) {
            Log::error('Order cancel failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['order' => 'Une erreur est survenue lors de l\'annulation. Veuillez reessayer.']);
        }

        // Remboursement automatique via la passerelle qui a encaisse
        $refundMsg = '';
        $order->refresh();
        if ($order->payment_status === 'refunded') {
            $refund = FedaPayService::refundAny($order);
            $refundMsg = $refund['success']
                ? ' Remboursement client effectue automatiquement.'
                : ' ' . $refund['message'];
        }

        \Illuminate\Support\Facades\Cache::forget('admin.dashboard');

        return back()->with('success', 'Commande ' . $order->reference . ' annulee.' . $refundMsg);
    }

    // ──────────────────────────────────────────────
    //  Contestation de litige (defense vendeur)
    // ──────────────────────────────────────────────

    public function submitDefense(Request $request, Order $order): RedirectResponse
    {
        if ((int) $order->vendor_id !== (int) auth()->id()) {
            abort(403);
        }

        if ($order->status !== OrderStatus::DISPUTED) {
            return back()->withErrors(['order' => 'Cette commande n\'est pas en litige.']);
        }

        $validated = $request->validate([
            'vendor_defense_message' => ['required', 'string', 'max:2000'],
            'vendor_defense_proof'   => ['nullable', 'image', 'max:5120'],
        ], [
            'vendor_defense_message.required' => 'Veuillez expliquer votre version des faits.',
            'vendor_defense_message.max'      => 'Le message ne doit pas depasser 2000 caracteres.',
            'vendor_defense_proof.image'      => 'La preuve doit etre une image (JPG, PNG).',
            'vendor_defense_proof.max'        => 'L\'image ne doit pas depasser 5 Mo.',
        ]);

        $data = ['vendor_defense_message' => $validated['vendor_defense_message']];

        if ($request->hasFile('vendor_defense_proof')) {
            $data['vendor_defense_proof'] = $request->file('vendor_defense_proof')
                ->store('orders/defense', 'public');
        }

        $order->update($data);

        return back()->with('success', 'Votre defense a ete soumise. L\'administration va examiner le dossier.');
    }

    // ──────────────────────────────────────────────
    //  Chat de litige e-commerce
    // ──────────────────────────────────────────────

    public function disputeChat(Order $order): InertiaResponse
    {
        if ((int) $order->vendor_id !== (int) auth()->id()) {
            abort(403);
        }

        $order->load(['product:id,name,image_path,price', 'influencer:id,name']);

        $messages = $order->disputeMessages()
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => match ($m->sender_type) {
                    'customer' => $order->customer_name,
                    'admin'    => 'Administration MANTOTA',
                    default    => $m->user?->name ?? 'Vendeur',
                },
                'message'     => $m->message,
                'created_at'  => $m->created_at,
            ]);

        $isActive = $order->status === OrderStatus::DISPUTED;

        return Inertia::render('Orders/DisputeChat', [
            'order'    => $order,
            'messages' => $messages,
            'isActive' => $isActive,
        ]);
    }

    public function storeDisputeMessage(Request $request, Order $order): RedirectResponse
    {
        if ((int) $order->vendor_id !== (int) auth()->id()) {
            abort(403);
        }

        if ($order->status !== OrderStatus::DISPUTED) {
            return back()->withErrors(['message' => 'Ce litige est clos.']);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $moderation = app(ChatModeratorService::class)->moderate($validated['message'], 'Litige e-commerce', auth()->user()->name);

        $msg = OrderDisputeMessage::create([
            'order_id'         => $order->id,
            'sender_type'      => 'vendor',
            'user_id'          => auth()->id(),
            'message'          => $moderation['text'],
            'is_flagged'       => $moderation['is_flagged'],
            'original_message' => $moderation['original_message'],
        ]);

        broadcast(new NewDisputeMessage(
            orderId:    $order->id,
            senderType: 'vendor',
            userId:     auth()->id(),
            message:    $moderation['text'],
            senderName: auth()->user()->name,
            createdAt:  $msg->created_at->toISOString(),
        ))->toOthers();

        $flash = $moderation['is_flagged']
            ? back()->with('warning', 'Votre message a ete signale pour verification.')
            : back();

        return $flash;
    }
}
