<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketReplyNotification;
use App\Services\ChatModeratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/Support/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:255'],
            'subject'  => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:bug,paiement,question'],
            'message'  => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        $moderation = app(ChatModeratorService::class)->moderate($validated['message'], 'Support (public)', $user ? $user->name : ($validated['name'] ?? 'Visiteur'));

        $ticket = SupportTicket::create([
            'user_id'        => $user?->id,
            'guest_name'     => $user ? null : $validated['name'],
            'guest_email'    => $user ? null : $validated['email'],
            'reference_code' => SupportTicket::generateReference(),
            'subject'        => $validated['subject'],
            'category'       => $validated['category'],
            'status'         => 'open',
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id'           => $user?->id,
            'is_admin'          => false,
            'body'              => $moderation['text'],
            'is_flagged'        => $moderation['is_flagged'],
            'original_message'  => $moderation['original_message'],
        ]);

        return redirect()->route('support.show', $ticket->reference_code)
            ->with('success', 'Ticket cree avec succes. Votre code de suivi : ' . $ticket->reference_code);
    }

    public function track(): Response
    {
        return Inertia::render('Public/Support/Track');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $request->validate([
            'reference_code' => ['required', 'string', 'max:20'],
        ]);

        $ticket = SupportTicket::where('reference_code', $request->reference_code)->first();

        if (! $ticket) {
            return back()->withErrors(['reference_code' => 'Aucun ticket trouve avec ce code.']);
        }

        return redirect()->route('support.show', $ticket->reference_code);
    }

    public function show(string $reference): Response
    {
        $ticket = SupportTicket::where('reference_code', $reference)
            ->with(['messages.user', 'user'])
            ->firstOrFail();

        return Inertia::render('Public/Support/Show', [
            'ticket'   => $ticket,
            'messages' => $ticket->messages,
        ]);
    }

    public function reply(Request $request, string $reference): RedirectResponse
    {
        $ticket = SupportTicket::where('reference_code', $reference)->firstOrFail();

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $moderation = app(ChatModeratorService::class)->moderate($request->message, 'Support (public)', $request->user()?->name ?? 'Visiteur');

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id'           => $request->user()?->id,
            'is_admin'          => false,
            'body'              => $moderation['text'],
            'is_flagged'        => $moderation['is_flagged'],
            'original_message'  => $moderation['original_message'],
        ]);

        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open']);
        }

        // Notifier tous les admins
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new SupportTicketReplyNotification($ticket, $request->user()?->name ?? 'Visiteur', false));

        return back()->with('success', 'Message envoye.');
    }
}
