<?php

declare(strict_types=1);

namespace App\Http\Controllers\Influencer;

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
    public function index(Request $request): Response
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->withCount('messages')
            ->latest()
            ->paginate(15);

        return Inertia::render('Support/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Support/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject'  => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:bug,paiement,question'],
            'message'  => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        $moderation = app(ChatModeratorService::class)->moderate($validated['message'], 'Support créateur de contenu', $user->name);

        $ticket = SupportTicket::create([
            'user_id'        => $user->id,
            'guest_name'     => null,
            'guest_email'    => null,
            'reference_code' => SupportTicket::generateReference(),
            'subject'        => $validated['subject'],
            'category'       => $validated['category'],
            'status'         => 'open',
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id'           => $user->id,
            'is_admin'          => false,
            'body'              => $moderation['text'],
            'is_flagged'        => $moderation['is_flagged'],
            'original_message'  => $moderation['original_message'],
        ]);

        return redirect()->route('influencer.support.show', $ticket)
            ->with('success', 'Ticket cree avec succes. Reference : ' . $ticket->reference_code);
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);

        $ticket->load(['messages.user', 'user']);

        return Inertia::render('Support/Show', [
            'ticket'   => $ticket,
            'messages' => $ticket->messages,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $moderation = app(ChatModeratorService::class)->moderate($request->message, 'Support créateur de contenu', $request->user()->name);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id'           => $request->user()->id,
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
        Notification::send($admins, new SupportTicketReplyNotification($ticket, $request->user()->name, false));

        return back()->with('success', 'Message envoye.');
    }
}
