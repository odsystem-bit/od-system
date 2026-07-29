<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketReplyNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SupportTicket::with('user')
            ->withCount('messages')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return Inertia::render('Support/Index', [
            'tickets' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['status', 'category']),
        ]);
    }

    public function show(SupportTicket $ticket): Response
    {
        $ticket->load(['messages.user', 'user']);

        return Inertia::render('Support/Show', [
            'ticket'   => $ticket,
            'messages' => $ticket->messages,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id'           => $request->user()->id,
            'is_admin'          => true,
            'body'              => $request->message,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'pending']);
        }

        // Notifier le proprietaire du ticket
        if ($ticket->user) {
            $ticket->user->notify(new SupportTicketReplyNotification($ticket, $request->user()->name, true));
        }

        return back()->with('success', 'Reponse envoyee.');
    }

    public function resolve(SupportTicket $ticket): RedirectResponse
    {
        $ticket->update(['status' => 'resolved']);

        return back()->with('success', 'Ticket marque comme resolu.');
    }
}
