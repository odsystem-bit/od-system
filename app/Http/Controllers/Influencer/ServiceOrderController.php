<?php

declare(strict_types=1);

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ServiceOrderController extends Controller
{
    /**
     * Liste des commandes recues par le créateur de contenu.
     */
    public function index(): InertiaResponse
    {
        $orders = ServiceOrder::where('influencer_id', auth()->id())
            ->with([
                'service:id,title,type,duration',
                'vendor:id,name,business_name,shop_name,shop_logo_path',
                'product:id,name',
            ])
            ->latest()
            ->paginate(15);

        return Inertia::render('ServiceOrders/Index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Detail d'une commande.
     */
    public function show(ServiceOrder $order): InertiaResponse
    {
        $this->authorizeInfluencer($order);

        $order->load([
            'service:id,title,type,duration',
            'vendor:id,name,business_name,shop_name,shop_logo_path',
            'product:id,name,type,description,price,image_path',
            'product.images',
            'messages.sender:id,name,role',
        ]);

        return Inertia::render('ServiceOrders/Show', [
            'order'  => $order,
            'authId' => (int) auth()->id(),
        ]);
    }

    /**
     * Accepter une commande (passer en shooting).
     */
    public function accept(ServiceOrder $order): RedirectResponse
    {
        $this->authorizeInfluencer($order);

        if ($order->status !== ServiceOrder::STATUS_PENDING) {
            return redirect()->back()->withErrors(['status' => 'Cette commande ne peut plus etre acceptee.']);
        }

        $order->update(['status' => ServiceOrder::STATUS_SHOOTING]);

        return redirect()
            ->route('influencer.service-orders.index')
            ->with('success', 'Commande acceptee. Vous pouvez commencer la production.');
    }

    /**
     * Livrer la video.
     */
    public function deliver(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorizeInfluencer($order);

        if (! in_array($order->status, [ServiceOrder::STATUS_SHOOTING, ServiceOrder::STATUS_REVISION_REQUESTED], true)) {
            return redirect()->back()->withErrors(['status' => 'Cette commande ne peut pas recevoir de livraison dans son statut actuel.']);
        }

        $validated = $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:102400'],
        ], [
            'video.required'  => 'La video est obligatoire.',
            'video.mimetypes' => 'Formats acceptes : MP4, MOV, WebM.',
            'video.max'       => 'La video ne peut pas depasser 100 Mo.',
        ]);

        // Supprimer l'ancienne video si remplacement
        if ($order->video_path) {
            Storage::disk('public')->delete($order->video_path);
        }

        $videoPath = $request->file('video')->store('service-orders', 'public');

        $order->update([
            'video_path'        => $videoPath,
            'status'            => ServiceOrder::STATUS_DELIVERED,
            'delivered_at'      => now(),
            'revision_feedback' => null,
        ]);

        return redirect()
            ->route('influencer.service-orders.show', $order)
            ->with('success', 'Video livree. En attente de validation du vendeur.');
    }

    /**
     * Confirmer la reception de l'echantillon produit.
     */
    public function markSampleReceived(ServiceOrder $order): RedirectResponse
    {
        $this->authorizeInfluencer($order);

        if ($order->sample_status !== ServiceOrder::SAMPLE_SHIPPED) {
            return redirect()->back()->withErrors(['sample' => 'L\'echantillon ne peut pas etre confirme dans cet etat.']);
        }

        $order->update([
            'sample_status'        => ServiceOrder::SAMPLE_RECEIVED,
            'production_started_at' => now(),
        ]);

        return redirect()
            ->route('influencer.service-orders.show', $order)
            ->with('success', 'Reception confirmee. Vous pouvez commencer la production.');
    }

    /**
     * Annulation par le créateur de contenu (pending / shooting uniquement).
     * Remboursement Escrow → Balance du vendeur.
     */
    public function cancel(ServiceOrder $order): RedirectResponse
    {
        $this->authorizeInfluencer($order);

        if (! in_array($order->status, [ServiceOrder::STATUS_PENDING, ServiceOrder::STATUS_SHOOTING], true)) {
            return redirect()->back()->withErrors(['status' => 'Cette commande ne peut plus etre annulee.']);
        }

        DB::transaction(function () use ($order): void {
            $lockedOrder = ServiceOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedOrder->status, [ServiceOrder::STATUS_PENDING, ServiceOrder::STATUS_SHOOTING], true)) {
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
            ->route('influencer.service-orders.index')
            ->with('success', 'Commande annulee. Le vendeur a ete rembourse.');
    }

    // ──────────────────────────────────────────────
    //  Guard
    // ──────────────────────────────────────────────

    private function authorizeInfluencer(ServiceOrder $order): void
    {
        abort_unless((int) $order->influencer_id === (int) auth()->id(), 403);
    }
}
