<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderDisputeMessage;
use App\Models\ServiceOrderMessage;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FlaggedMessageController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $source = $request->input('source', 'all');

        $messages = collect();

        // Messages litiges e-commerce
        if ($source === 'all' || $source === 'disputes') {
            $disputes = OrderDisputeMessage::where('is_flagged', true)
                ->with(['order:id,reference', 'user:id,name,email'])
                ->latest()
                ->get()
                ->map(fn ($m) => [
                    'id'               => $m->id,
                    'source'           => 'Litige e-commerce',
                    'source_key'       => 'disputes',
                    'reference'        => $m->order?->reference ?? '-',
                    'sender_name'      => $m->user?->name ?? ($m->sender_type === 'customer' ? 'Client' : 'Inconnu'),
                    'sender_type'      => $m->sender_type,
                    'original_message' => $m->original_message,
                    'masked_message'   => $m->message,
                    'created_at'       => $m->created_at->toISOString(),
                ]);
            $messages = $messages->merge($disputes);
        }

        // Messages studios UGC
        if ($source === 'all' || $source === 'studios') {
            $studios = ServiceOrderMessage::where('is_flagged', true)
                ->with(['serviceOrder:id', 'sender:id,name,email'])
                ->latest()
                ->get()
                ->map(fn ($m) => [
                    'id'               => $m->id,
                    'source'           => 'Studio UGC',
                    'source_key'       => 'studios',
                    'reference'        => 'Commande #' . $m->service_order_id,
                    'sender_name'      => $m->sender?->name ?? 'Inconnu',
                    'sender_type'      => 'user',
                    'original_message' => $m->original_message,
                    'masked_message'   => $m->message,
                    'created_at'       => $m->created_at->toISOString(),
                ]);
            $messages = $messages->merge($studios);
        }

        // Messages support
        if ($source === 'all' || $source === 'support') {
            $support = TicketMessage::where('is_flagged', true)
                ->with(['ticket:id,reference_code,subject', 'user:id,name,email'])
                ->latest()
                ->get()
                ->map(fn ($m) => [
                    'id'               => $m->id,
                    'source'           => 'Support',
                    'source_key'       => 'support',
                    'reference'        => $m->ticket?->reference_code ?? '-',
                    'sender_name'      => $m->user?->name ?? 'Visiteur',
                    'sender_type'      => $m->is_admin ? 'admin' : 'user',
                    'original_message' => $m->original_message,
                    'masked_message'   => $m->body,
                    'created_at'       => $m->created_at->toISOString(),
                ]);
            $messages = $messages->merge($support);
        }

        // Trier par date decroissante
        $messages = $messages->sortByDesc('created_at')->values();

        return Inertia::render('FlaggedMessages/Index', [
            'messages'      => $messages,
            'currentSource' => $source,
            'counts'        => [
                'all'      => OrderDisputeMessage::where('is_flagged', true)->count()
                            + ServiceOrderMessage::where('is_flagged', true)->count()
                            + TicketMessage::where('is_flagged', true)->count(),
                'disputes' => OrderDisputeMessage::where('is_flagged', true)->count(),
                'studios'  => ServiceOrderMessage::where('is_flagged', true)->count(),
                'support'  => TicketMessage::where('is_flagged', true)->count(),
            ],
        ]);
    }
}
