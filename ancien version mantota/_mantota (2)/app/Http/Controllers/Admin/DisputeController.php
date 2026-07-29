<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Events\NewDisputeMessage;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDisputeMessage;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Wallet;
use App\Services\AuditLogService;
use App\Services\Payment\FedaPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * DisputeController — Tribunal Admin MANTOTA.
 *
 * Gere les commandes en statut DISPUTED.
 * Deux verdicts possibles :
 *  1. Rembourser le client  → cancelled, fonds retires des pending_balance.
 *  2. Donner raison au vendeur → delivered, fonds transferes vers balance.
 */
class DisputeController extends Controller
{
    /**
     * Liste des commandes en litige.
     */
    public function index(): InertiaResponse
    {
        $disputes = Order::query()
            ->where('status', OrderStatus::DISPUTED)
            ->with(['vendor:id,name,email', 'influencer:id,name,email', 'product:id,name'])
            ->orderByDesc('updated_at')
            ->paginate(25);

        $serviceDisputes = ServiceOrder::query()
            ->where('status', ServiceOrder::STATUS_DISPUTED)
            ->with(['vendor:id,name,email', 'influencer:id,name,email', 'service:id,title,type'])
            ->withExists(['messages as has_flagged_messages' => fn ($q) => $q->where('is_flagged', true)])
            ->orderByDesc('updated_at')
            ->paginate(25, ['*'], 'service_page');

        return Inertia::render('Disputes/Index', [
            'disputes'        => $disputes,
            'serviceDisputes' => $serviceDisputes,
        ]);
    }

    /**
     * Dossier litige e-commerce — Timeline chronologique de la commande.
     */
    public function show(Order $order): InertiaResponse
    {
        $order->load(['vendor:id,name,email,phone', 'influencer:id,name,email', 'product:id,name']);

        // Build timeline events from the order data
        $timeline = [];

        $timeline[] = [
            'date'  => $order->created_at,
            'label' => 'Commande creee',
            'detail' => 'Reference ' . $order->reference . ' -- ' . number_format((float) $order->amount_paid, 0, ',', ' ') . ' FCFA',
            'color' => 'teal',
        ];

        if ($order->delivery_pin) {
            $timeline[] = [
                'date'  => $order->created_at,
                'label' => 'Code OTP genere',
                'detail' => 'Code de confirmation attribue au client.',
                'color' => 'slate',
            ];
        }

        if ($order->delivery_guy_name) {
            $timeline[] = [
                'date'  => $order->updated_at,
                'label' => 'Livreur assigne',
                'detail' => $order->delivery_guy_name . ($order->delivery_guy_phone ? ' -- ' . $order->delivery_guy_phone : ''),
                'color' => 'blue',
            ];
        }

        if ($order->status === OrderStatus::DISPUTED) {
            $timeline[] = [
                'date'  => $order->updated_at,
                'label' => 'Litige ouvert',
                'detail' => $order->dispute_reason ?: 'Le client a conteste cette commande.',
                'color' => 'red',
            ];

            if ($order->vendor_defense_message) {
                $timeline[] = [
                    'date'  => $order->updated_at,
                    'label' => 'Defense du vendeur soumise',
                    'detail' => $order->vendor_defense_message,
                    'color' => 'teal',
                ];
            }
        }

        return Inertia::render('Disputes/Show', [
            'order'    => $order,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Verdict : Rembourser le client.
     *
     * - Commande → cancelled
     * - vendor_earnings retire du pending_balance du vendeur
     * - commission_amount retiree du pending_balance du créateur de contenu
     */
    public function refundClient(Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::DISPUTED) {
            return back()->with('error', 'Cette commande n\'est pas en litige.');
        }

        $wasPaid = $order->payment_status === 'paid';

        try {
            DB::transaction(function () use ($order): void {
                // Verrouiller la commande
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

                // Double-check apres verrouillage
                if ($lockedOrder->status !== OrderStatus::DISPUTED) {
                    return;
                }

                // Retirer les gains de l'escrow_balance du vendeur
                if ($lockedOrder->vendor_id) {
                    $vendorWallet = Wallet::where('user_id', $lockedOrder->vendor_id)->lockForUpdate()->first();
                    if ($vendorWallet) {
                        $vendorWallet->escrow_balance = max(0, (float) $vendorWallet->escrow_balance - (float) $lockedOrder->vendor_earnings);
                        $vendorWallet->save();
                    }
                }

                // Retirer la commission de l'escrow_balance du créateur de contenu
                if ($lockedOrder->influencer_id && (float) $lockedOrder->commission_amount > 0) {
                    $influencerWallet = Wallet::where('user_id', $lockedOrder->influencer_id)->lockForUpdate()->first();
                    if ($influencerWallet) {
                        $influencerWallet->escrow_balance = max(0, (float) $influencerWallet->escrow_balance - (float) $lockedOrder->commission_amount);
                        $influencerWallet->save();
                    }
                }

                // Restaurer le stock produit physique
                $product = Product::find($lockedOrder->product_id);
                if ($product && $product->isPhysical() && $product->stock !== null) {
                    $product->increment('stock');
                }

                // Audit trail — reversal escrow vendeur
                if ($lockedOrder->vendor_id) {
                    Transaction::create([
                        'user_id'        => $lockedOrder->vendor_id,
                        'type'           => 'fee',
                        'amount_target'  => (float) $lockedOrder->vendor_earnings,
                        'gateway_fee'    => 0.00,
                        'mantota_markup' => 0.00,
                        'amount_total'   => (float) $lockedOrder->vendor_earnings,
                        'status'         => 'completed',
                        'reference'      => 'DISPUTE-REFUND-VENDOR-' . $lockedOrder->reference,
                        'description'    => 'Litige — Remboursement client, escrow vendeur reverse #' . $lockedOrder->reference,
                    ]);
                }

                // Audit trail — reversal escrow créateur de contenu
                if ($lockedOrder->influencer_id && (float) $lockedOrder->commission_amount > 0) {
                    Transaction::create([
                        'user_id'        => $lockedOrder->influencer_id,
                        'type'           => 'fee',
                        'amount_target'  => (float) $lockedOrder->commission_amount,
                        'gateway_fee'    => 0.00,
                        'mantota_markup' => 0.00,
                        'amount_total'   => (float) $lockedOrder->commission_amount,
                        'status'         => 'completed',
                        'reference'      => 'DISPUTE-REFUND-COMM-' . $lockedOrder->reference,
                        'description'    => 'Litige — Remboursement client, commission reverse #' . $lockedOrder->reference,
                    ]);
                }

                // Passer la commande en disputed_resolved + payment_status refunded
                $lockedOrder->update([
                    'status'         => OrderStatus::DISPUTED_RESOLVED,
                    'payment_status' => $lockedOrder->payment_status === 'paid' ? 'refunded' : $lockedOrder->payment_status,
                ]);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dispute refund failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors du remboursement. Veuillez reessayer.');
        }

        \Illuminate\Support\Facades\Cache::forget('admin.dashboard');

        // Remboursement automatique via la passerelle uniquement si le paiement avait ete encaisse
        $refundMsg = '';
        $order->refresh();
        if ($wasPaid && $order->payment_status === 'refunded') {
            $refund = FedaPayService::refundAny($order);
            $refundMsg = $refund['success']
                ? ' Remboursement client effectue automatiquement.'
                : ' ' . $refund['message'];
        }

        AuditLogService::log('refund_client', 'Order', $order->id,
            ['status' => 'disputed'],
            ['status' => 'disputed_resolved', 'verdict' => 'client_rembourse']
        );

        return back()->with('success', "Commande {$order->reference} : client rembourse, fonds retires." . $refundMsg);
    }

    /**
     * Verdict : Donner raison au vendeur.
     *
     * - Commande → delivered
     * - vendor_earnings : escrow_balance → balance du vendeur
     * - commission_amount : escrow_balance → balance du créateur de contenu
     */
    public function favorVendor(Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::DISPUTED) {
            return back()->with('error', 'Cette commande n\'est pas en litige.');
        }

        try {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // Transferer les gains de l'escrow_balance vers balance du vendeur
            if ($lockedOrder->vendor_id) {
                $vendorWallet = Wallet::where('user_id', $lockedOrder->vendor_id)->lockForUpdate()->first();
                if ($vendorWallet) {
                    $earnings = (float) $lockedOrder->vendor_earnings;
                    $vendorWallet->escrow_balance = max(0, (float) $vendorWallet->escrow_balance - $earnings);
                    $vendorWallet->balance = (float) $vendorWallet->balance + $earnings;
                    $vendorWallet->save();

                    Transaction::create([
                        'user_id'        => $lockedOrder->vendor_id,
                        'type'           => 'earning',
                        'amount_target'  => $earnings,
                        'gateway_fee'    => 0.00,
                        'mantota_markup' => 0.00,
                        'amount_total'   => $earnings,
                        'status'         => 'completed',
                        'reference'      => 'DISPUTE-VENDOR-' . $lockedOrder->reference,
                        'description'    => 'Litige resolu — Commande #' . $lockedOrder->reference,
                    ]);
                }
            }

            // Transferer la commission de l'escrow_balance vers balance du créateur de contenu
            if ($lockedOrder->influencer_id && (float) $lockedOrder->commission_amount > 0) {
                $influencerWallet = Wallet::where('user_id', $lockedOrder->influencer_id)->lockForUpdate()->first();
                if ($influencerWallet) {
                    $commission = (float) $lockedOrder->commission_amount;
                    $influencerWallet->escrow_balance = max(0, (float) $influencerWallet->escrow_balance - $commission);
                    $influencerWallet->balance = (float) $influencerWallet->balance + $commission;
                    $influencerWallet->save();

                    Transaction::create([
                        'user_id'        => $lockedOrder->influencer_id,
                        'type'           => 'earning',
                        'amount_target'  => $commission,
                        'gateway_fee'    => 0.00,
                        'mantota_markup' => 0.00,
                        'amount_total'   => $commission,
                        'status'         => 'completed',
                        'reference'      => 'DISPUTE-COMM-' . $lockedOrder->reference,
                        'description'    => 'Commission CPA — Litige resolu #' . $lockedOrder->reference,
                    ]);
                }
            }

            // Passer la commande en disputed_resolved
            $lockedOrder->status = OrderStatus::DISPUTED_RESOLVED;
            $lockedOrder->save();
        });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dispute favor vendor failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la liberation des fonds. Veuillez reessayer.');
        }

        \Illuminate\Support\Facades\Cache::forget('admin.dashboard');

        AuditLogService::log('favor_vendor', 'Order', $order->id,
            ['status' => 'disputed'],
            ['status' => 'disputed_resolved', 'verdict' => 'vendeur_valide']
        );

        return back()->with('success', "Commande {$order->reference} : vendeur valide, fonds liberes.");
    }

    // ──────────────────────────────────────────────
    //  Litiges UGC / MANTOTA Studios (ServiceOrder)
    // ──────────────────────────────────────────────

    private function ugcCommissionRate(): float
    {
        return (float) mantota_setting('ugc_studio_fee_percent', 15) / 100;
    }

    /**
     * Verdict : Rembourser le vendeur (escrow → balance vendeur).
     */
    public function refundVendorService(ServiceOrder $serviceOrder): RedirectResponse
    {
        if ($serviceOrder->status !== ServiceOrder::STATUS_DISPUTED) {
            return back()->with('error', 'Cette commande UGC n\'est pas en litige.');
        }

        try {
            DB::transaction(function () use ($serviceOrder): void {
                $lockedSO = ServiceOrder::where('id', $serviceOrder->id)->lockForUpdate()->firstOrFail();

                if ($lockedSO->status !== ServiceOrder::STATUS_DISPUTED) {
                    return;
                }

                $vendorWallet = Wallet::where('user_id', $lockedSO->vendor_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = (float) $lockedSO->amount;
                $vendorWallet->escrow_balance = max(0, (float) $vendorWallet->escrow_balance - $amount);
                $vendorWallet->balance        = (float) $vendorWallet->balance + $amount;
                $vendorWallet->save();

                Transaction::create([
                    'user_id'        => $lockedSO->vendor_id,
                    'type'           => 'earning',
                    'amount_target'  => $amount,
                    'gateway_fee'    => 0.00,
                    'mantota_markup' => 0.00,
                    'amount_total'   => $amount,
                    'status'         => 'completed',
                    'reference'      => 'DISPUTE-UGC-REFUND-' . $lockedSO->id,
                    'description'    => 'Litige UGC — Vendeur rembourse, escrow libere #' . $lockedSO->id,
                ]);

                $lockedSO->update(['status' => ServiceOrder::STATUS_REJECTED]);
            });
        } catch (\Throwable $e) {
            Log::error('ServiceOrder dispute refund vendor failed', ['so_id' => $serviceOrder->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors du remboursement. Veuillez reessayer.');
        }

        AuditLogService::log('refund_vendor_ugc', 'ServiceOrder', $serviceOrder->id,
            ['status' => 'disputed'],
            ['status' => 'rejected', 'verdict' => 'vendeur_rembourse']
        );

        return back()->with('success', "Commande UGC #{$serviceOrder->id} : vendeur rembourse.");
    }

    /**
     * Verdict : Donner raison au créateur de contenu (escrow → balance créateur de contenu - commission).
     */
    public function favorInfluencerService(ServiceOrder $serviceOrder): RedirectResponse
    {
        if ($serviceOrder->status !== ServiceOrder::STATUS_DISPUTED) {
            return back()->with('error', 'Cette commande UGC n\'est pas en litige.');
        }

        try {
            DB::transaction(function () use ($serviceOrder): void {
                $lockedSO = ServiceOrder::where('id', $serviceOrder->id)->lockForUpdate()->firstOrFail();

                if ($lockedSO->status !== ServiceOrder::STATUS_DISPUTED) {
                    return;
                }

                $vendorWallet = Wallet::where('user_id', $lockedSO->vendor_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $influencerWallet = Wallet::firstOrCreate(
                    ['user_id' => $lockedSO->influencer_id],
                    ['balance' => 0, 'pending_balance' => 0, 'escrow_balance' => 0]
                );
                $influencerWallet = Wallet::where('id', $influencerWallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount     = (float) $lockedSO->amount;
                $commission = round($amount * $this->ugcCommissionRate(), 2);
                $payout     = round($amount - $commission, 2);

                $vendorWallet->escrow_balance = max(0, (float) $vendorWallet->escrow_balance - $amount);
                $vendorWallet->save();

                $influencerWallet->balance = (float) $influencerWallet->balance + $payout;
                $influencerWallet->save();

                Transaction::create([
                    'user_id'        => $lockedSO->influencer_id,
                    'type'           => 'earning',
                    'amount_target'  => $payout,
                    'gateway_fee'    => 0.00,
                    'mantota_markup' => $commission,
                    'amount_total'   => $payout,
                    'status'         => 'completed',
                    'reference'      => 'DISPUTE-UGC-INFLUENCER-' . $lockedSO->id,
                    'description'    => 'Litige UGC — Createur de contenu paye #' . $lockedSO->id,
                ]);

                $lockedSO->update(['status' => ServiceOrder::STATUS_COMPLETED]);
            });
        } catch (\Throwable $e) {
            Log::error('ServiceOrder dispute favor influencer failed', ['so_id' => $serviceOrder->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la liberation des fonds. Veuillez reessayer.');
        }

        AuditLogService::log('favor_influencer_ugc', 'ServiceOrder', $serviceOrder->id,
            ['status' => 'disputed'],
            ['status' => 'completed', 'verdict' => 'influenceur_paye']
        );

        return back()->with('success', "Commande UGC #{$serviceOrder->id} : createur de contenu paye.");
    }

    // ──────────────────────────────────────────────
    //  Chat litige e-commerce — Tribunal Admin
    // ──────────────────────────────────────────────

    /**
     * Affiche le chat de mediation pour un litige e-commerce.
     */
    public function chat(Order $order): InertiaResponse
    {
        $order->load([
            'vendor:id,name,email,phone,business_name',
            'influencer:id,name,email',
            'product:id,name,image_path,price',
        ]);

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

        return Inertia::render('Disputes/EcommerceChat', [
            'order'    => $order,
            'messages' => $messages,
        ]);
    }

    /**
     * Admin envoie un message dans le chat de litige e-commerce.
     */
    public function storeMessage(Request $request, Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::DISPUTED) {
            return back()->withErrors(['message' => 'Ce litige est clos.']);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $msg = OrderDisputeMessage::create([
            'order_id'    => $order->id,
            'sender_type' => 'admin',
            'user_id'     => auth()->id(),
            'message'     => $validated['message'],
        ]);

        broadcast(new NewDisputeMessage(
            orderId:    $order->id,
            senderType: 'admin',
            userId:     auth()->id(),
            message:    $validated['message'],
            senderName: 'Administration MANTOTA',
            createdAt:  $msg->created_at->toISOString(),
        ))->toOthers();

        return back();
    }
}
