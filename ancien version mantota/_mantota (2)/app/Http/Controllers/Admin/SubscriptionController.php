<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SubscriptionController extends Controller
{
    /**
     * Liste tous les abonnements ambassadeur (actifs et expires).
     */
    public function index(Request $request): InertiaResponse
    {
        $query = User::query()
            ->whereNotNull('ambassador_subscribed_at')
            ->select([
                'id', 'name', 'email', 'role', 'is_ambassador',
                'ambassador_tier', 'ambassador_source', 'ambassador_subscribed_at', 'ambassador_expires_at',
            ]);

        // Filtre par statut
        $filter = $request->input('filter', 'all');
        if ($filter === 'active') {
            $query->where('is_ambassador', true)
                  ->where('ambassador_expires_at', '>', now());
        } elseif ($filter === 'expired') {
            $query->where(function ($q) {
                $q->where('is_ambassador', false)
                  ->orWhere('ambassador_expires_at', '<=', now());
            });
        }

        // Recherche
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->latest('ambassador_subscribed_at')->paginate(20)->withQueryString();

        // Stats
        $totalActive = User::where('is_ambassador', true)
            ->where('ambassador_expires_at', '>', now())
            ->count();
        $totalExpired = User::whereNotNull('ambassador_subscribed_at')
            ->where(function ($q) {
                $q->where('is_ambassador', false)
                  ->orWhere('ambassador_expires_at', '<=', now());
            })
            ->count();
        $monthlyRevenue = (int) mantota_setting('ambassador_badge_price', 5000) * $totalActive;

        return Inertia::render('Subscriptions/Index', [
            'subscribers'    => $subscribers,
            'filter'         => $filter,
            'search'         => $search ?? '',
            'stats'          => [
                'active'         => $totalActive,
                'expired'        => $totalExpired,
                'monthly_revenue' => $monthlyRevenue,
            ],
        ]);
    }

    /**
     * Prolonger manuellement l'abonnement d'un utilisateur.
     */
    public function extend(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $days = (int) $validated['days'];

        // Si l'abonnement est encore actif, prolonger depuis l'expiry
        $startFrom = ($user->is_ambassador && $user->ambassador_expires_at && $user->ambassador_expires_at->isFuture())
            ? $user->ambassador_expires_at
            : now();

        $user->update([
            'is_ambassador'            => true,
            'ambassador_tier'          => $user->ambassador_tier ?: 'bronze',
            'ambassador_source'        => $user->ambassador_source ?: 'admin',
            'ambassador_subscribed_at' => $user->ambassador_subscribed_at ?: now(),
            'ambassador_expires_at'    => $startFrom->copy()->addDays($days),
        ]);

        return back()->with('success', "Abonnement de {$user->name} prolonge de {$days} jours.");
    }

    /**
     * Revoquer l'abonnement d'un utilisateur.
     */
    public function revoke(User $user): RedirectResponse
    {
        $user->update([
            'is_ambassador'          => false,
            'ambassador_tier'        => null,
            'ambassador_source'      => null,
            'ambassador_expires_at'  => now(),
        ]);

        return back()->with('success', "Abonnement de {$user->name} revoque.");
    }
}
