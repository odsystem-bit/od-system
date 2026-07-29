<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\OrderStatus;
use App\Events\NewDisputeMessage;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDisputeMessage;
use App\Services\ChatModeratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * PublicDisputeController — Chat de litige public pour le client (sans compte).
 *
 * Securite : acces via tracking_token uniquement.
 * Le client n'a acces qu'en statut DISPUTED. Une fois le litige resolu,
 * l'acces aux messages est TOTALEMENT revoque.
 */
class PublicDisputeController extends Controller
{
    /**
     * Affiche le chat de litige public.
     */
    public function show(Request $request, Order $order): InertiaResponse
    {
        $this->authorizeToken($request, $order);

        $isActive = $order->status === OrderStatus::DISPUTED;

        $messages = [];
        if ($isActive) {
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
        }

        return Inertia::render('Shop/DisputeChat', [
            'order'    => [
                'id'             => $order->id,
                'reference'      => $order->reference,
                'status'         => $order->status->value,
                'customer_name'  => $order->customer_name,
                'dispute_reason' => $order->dispute_reason,
            ],
            'messages' => $messages,
            'isActive' => $isActive,
            'token'    => $order->tracking_token,
        ]);
    }

    /**
     * Le client envoie un message dans le chat de litige.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeToken($request, $order);

        if ($order->status !== OrderStatus::DISPUTED) {
            return back()->withErrors(['message' => 'Ce litige est clos.']);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'Le message ne peut pas etre vide.',
            'message.max'      => 'Le message ne peut pas depasser 2000 caracteres.',
        ]);

        $moderation = app(ChatModeratorService::class)->moderate($validated['message'], 'Litige e-commerce (client)', $order->customer_name ?? 'Client');

        $msg = OrderDisputeMessage::create([
            'order_id'         => $order->id,
            'sender_type'      => 'customer',
            'user_id'          => null,
            'message'          => $moderation['text'],
            'is_flagged'       => $moderation['is_flagged'],
            'original_message' => $moderation['original_message'],
        ]);

        broadcast(new NewDisputeMessage(
            orderId:    $order->id,
            senderType: 'customer',
            userId:     null,
            message:    $moderation['text'],
            senderName: $order->customer_name,
            createdAt:  $msg->created_at->toISOString(),
        ))->toOthers();

        return back();
    }

    /**
     * Verifie que le token de suivi est correct.
     */
    private function authorizeToken(Request $request, Order $order): void
    {
        $token = $request->query('token', $request->input('token', ''));

        if (! $token || ! hash_equals($order->tracking_token, (string) $token)) {
            abort(403, 'Token de suivi invalide.');
        }
    }
}
