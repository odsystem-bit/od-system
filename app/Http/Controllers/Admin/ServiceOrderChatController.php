<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ServiceOrderChatController extends Controller
{
    /**
     * Affiche le Chat complet d'une commande UGC.
     */
    public function show(ServiceOrder $serviceOrder): InertiaResponse
    {
        $serviceOrder->load([
            'vendor:id,name,email',
            'influencer:id,name,email',
            'service:id,title,type',
            'product:id,name',
            'messages.sender:id,name,role',
        ]);

        return Inertia::render('Disputes/Chat', [
            'order'  => $serviceOrder,
            'authId' => (int) auth()->id(),
        ]);
    }

    /**
     * L'Admin envoie un message dans le Chat (Intervention Divine).
     */
    public function store(Request $request, ServiceOrder $serviceOrder): RedirectResponse
    {
        $validated = $request->validate([
            'message'    => ['required_without:attachment', 'nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:10240'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('service-order-attachments', 'public');
        }

        ServiceOrderMessage::create([
            'service_order_id' => $serviceOrder->id,
            'sender_id'        => auth()->id(),
            'message'          => $validated['message'] ?? '',
            'attachment_path'  => $attachmentPath,
            'is_flagged'       => false,
            'original_message' => null,
        ]);

        return redirect()->route('admin.disputes.chat.show', $serviceOrder);
    }
}
