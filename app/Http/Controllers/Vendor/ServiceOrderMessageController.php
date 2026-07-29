<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderMessage;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;
use App\Services\ChatModeratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceOrderMessageController extends Controller
{
    public function store(Request $request, ServiceOrder $order, ChatModeratorService $moderator): RedirectResponse
    {
        abort_if(in_array($order->status, ['completed', 'cancelled', 'disputed_resolved']), 403, 'Cette commande est cloturee, vous ne pouvez plus envoyer de messages.');
        abort_unless((int) $order->vendor_id === (int) auth()->id(), 403);

        $validated = $request->validate([
            'message'    => ['required_without:attachment', 'nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:10240'],
        ]);

        $text       = $validated['message'] ?? '';
        $isFlagged  = false;
        $originalMsg = null;

        if ($text !== '') {
            $result     = $moderator->moderate($text, 'Studio UGC', auth()->user()->name);
            $text       = $result['text'];
            $isFlagged  = $result['is_flagged'];
            $originalMsg = $result['original_message'];
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('service-order-attachments', 'public');
        }

        ServiceOrderMessage::create([
            'service_order_id' => $order->id,
            'sender_id'        => auth()->id(),
            'message'          => $text,
            'attachment_path'  => $attachmentPath,
            'is_flagged'       => $isFlagged,
            'original_message' => $originalMsg,
        ]);

        // Notifier le créateur de contenu d'un nouveau message
        if ($influencer = User::find($order->influencer_id)) {
            $influencer->notify(new NewChatMessageNotification($order, auth()->user()->name));
        }

        $redirect = redirect()->route('vendor.service-orders.show', $order);

        if ($isFlagged || $originalMsg !== null) {
            $redirect->with('warning', 'Avertissement de securite : Contenu suspect detecte et masque. Restez sur MANTOTA pour garantir votre paiement.');
        }

        return $redirect;
    }
}
