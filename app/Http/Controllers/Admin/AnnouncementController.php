<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AnnouncementController extends Controller
{
    public function index(): InertiaResponse
    {
        $announcements = Announcement::latest()->paginate(20);

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message'     => ['required', 'string', 'max:500'],
            'target_role' => ['required', Rule::in(['all', 'vendor', 'influencer', 'admin'])],
            'is_active'   => ['sometimes', 'boolean'],
            'send_email'  => ['sometimes', 'boolean'],
        ]);

        $sendEmail = (bool) ($validated['send_email'] ?? false);
        unset($validated['send_email']);

        $announcement = Announcement::create($validated);

        // Notifier les utilisateurs cibles
        $query = User::query();
        if ($validated['target_role'] !== 'all') {
            $query->where('role', $validated['target_role']);
        }
        $users = $query->get();
        Notification::send($users, new AnnouncementNotification($announcement, $sendEmail));

        return back()->with('success', 'Annonce creee avec succes.' . ($sendEmail ? ' Un email a ete envoye.' : ''));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'message'     => ['required', 'string', 'max:500'],
            'target_role' => ['required', Rule::in(['all', 'vendor', 'influencer', 'admin'])],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $announcement->update($validated);

        return back()->with('success', 'Annonce mise a jour.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Annonce supprimee.');
    }
}
