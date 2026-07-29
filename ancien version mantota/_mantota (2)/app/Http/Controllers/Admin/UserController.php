<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignTier;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\KycLog;
use App\Models\Transaction;
use App\Models\Order;
use App\Models\ServiceOrder;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * CRM Pro — 3 onglets (staff / vendor / influencer) + filtres pays/statut.
     */
    public function index(Request $request): InertiaResponse
    {
        $tab = $request->input('tab', 'vendor');

        $roleMap = [
            'staff'      => UserRole::ADMIN,
            'vendor'     => UserRole::VENDOR,
            'influencer' => UserRole::INFLUENCER,
        ];

        $query = User::withTrashed()
            ->where('role', $roleMap[$tab] ?? UserRole::VENDOR)
            ->leftJoin('wallets', 'users.id', '=', 'wallets.user_id')
            ->select('users.*', 'wallets.balance as wallet_balance')
            ->orderByDesc('users.created_at');

        if ($search = $request->input('search')) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('users.name', 'like', "%{$escaped}%")
                  ->orWhere('users.email', 'like', "%{$escaped}%")
                  ->orWhere('users.phone', 'like', "%{$escaped}%");
            });
        }

        if ($country = $request->input('country')) {
            $query->where('users.country', $country);
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'banned'  => $query->where('users.is_banned', true),
                'active'  => $query->where('users.is_banned', false),
                'vip'     => $query->where('users.is_vip', true),
                default   => null,
            };
        }

        $users = $query->paginate(25)->withQueryString();

        // Pays distincts pour le filtre
        $countries = User::whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return Inertia::render('Users/Index', [
            'users'     => $users,
            'countries' => $countries,
            'filters'   => $request->only(['search', 'tab', 'country', 'status']),
        ]);
    }

    /**
     * Dossier 360° — Profil complet d'un utilisateur avec historique.
     */
    public function show(User $user): InertiaResponse
    {
        $user->load('wallet');

        // Historique KYC
        $kycLogs = KycLog::where('user_id', $user->id)
            ->with('admin:id,name')
            ->orderByDesc('created_at')
            ->get();

        // Historique financier (retraits)
        $withdrawals = Transaction::where('user_id', $user->id)
            ->where('type', 'withdrawal')
            ->orderByDesc('created_at')
            ->get();

        // Litiges e-commerce (en tant que vendeur)
        $orderDisputes = Order::where(function ($q) use ($user) {
                $q->where('vendor_id', $user->id)
                  ->orWhere('influencer_id', $user->id);
            })
            ->whereIn('status', ['disputed', 'cancelled'])
            ->with('product:id,name')
            ->orderByDesc('updated_at')
            ->get();

        // Litiges UGC (en tant que vendeur ou créateur de contenu)
        $serviceDisputes = ServiceOrder::where(function ($q) use ($user) {
                $q->where('vendor_id', $user->id)
                  ->orWhere('influencer_id', $user->id);
            })
            ->whereIn('status', ['disputed', 'rejected'])
            ->with('service:id,title')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Users/Show', [
            'profileUser'     => $user,
            'kycLogs'         => $kycLogs,
            'withdrawals'     => $withdrawals,
            'orderDisputes'   => $orderDisputes,
            'serviceDisputes' => $serviceDisputes,
        ]);
    }

    /**
     * Audit Social : corrige les abonnes et force le Tier.
     */
    public function updateSocials(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'tiktok_followers'    => ['required', 'integer', 'min:0'],
            'instagram_followers' => ['required', 'integer', 'min:0'],
            'facebook_followers'  => ['required', 'integer', 'min:0'],
            'youtube_followers'   => ['required', 'integer', 'min:0'],
            'snapchat_followers'  => ['required', 'integer', 'min:0'],
            'tier'                => ['nullable', 'string', Rule::in(['bronze', 'argent', 'or', 'auto'])],
        ]);

        $oldValues = $user->only(['tiktok_followers', 'instagram_followers', 'facebook_followers', 'youtube_followers', 'snapchat_followers', 'tier']);

        // Robot : si tier est 'auto' ou absent, calculer automatiquement
        $totalFollowers = (int) ($validated['tiktok_followers'] ?? 0)
                        + (int) ($validated['instagram_followers'] ?? 0)
                        + (int) ($validated['facebook_followers'] ?? 0)
                        + (int) ($validated['youtube_followers'] ?? 0)
                        + (int) ($validated['snapchat_followers'] ?? 0);

        if (empty($validated['tier']) || $validated['tier'] === 'auto') {
            $validated['tier'] = CampaignTier::fromFollowers($totalFollowers)->value;
        }

        $user->update($validated);

        AuditLogService::log('update_socials', 'User', $user->id, $oldValues, $validated);

        return back()->with('success', "Audit social de {$user->name} enregistre. Palier : {$validated['tier']}.");
    }

    public function toggleBan(User $user): RedirectResponse
    {
        $oldBanned = $user->is_banned;
        $user->forceFill(['is_banned' => ! $user->is_banned])->save();

        AuditLogService::log($user->is_banned ? 'ban_user' : 'unban_user', 'User', $user->id,
            ['is_banned' => $oldBanned],
            ['is_banned' => $user->is_banned]
        );

        $status = $user->is_banned ? 'banni' : 'debanni';

        return back()->with('success', "{$user->name} a ete {$status}.");
    }

    /**
     * Verrouille / déverrouille le portefeuille d'un utilisateur.
     * Un portefeuille verrouillé bloque tout retrait, transfert et achat.
     */
    public function toggleWalletLock(Request $request, User $user): RedirectResponse
    {
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (! $wallet) {
            return back()->with('error', 'Cet utilisateur n\'a pas de portefeuille.');
        }

        $wasLocked = $wallet->is_locked;

        if ($wasLocked) {
            // Déverrouiller
            $wallet->forceFill([
                'is_locked'   => false,
                'lock_reason' => null,
                'locked_at'   => null,
            ])->save();
        } else {
            // Verrouiller
            $validated = $request->validate([
                'lock_reason' => ['required', 'string', 'max:500'],
            ]);

            $wallet->forceFill([
                'is_locked'   => true,
                'lock_reason' => $validated['lock_reason'],
                'locked_at'   => now(),
            ])->save();
        }

        AuditLogService::log($wallet->is_locked ? 'lock_wallet' : 'unlock_wallet', 'Wallet', $wallet->id,
            ['is_locked' => $wasLocked],
            ['is_locked' => $wallet->is_locked, 'lock_reason' => $wallet->lock_reason]
        );

        $status = $wallet->is_locked ? 'verrouille' : 'deverrouille';

        return back()->with('success', "Portefeuille de {$user->name} {$status}.");
    }

    public function toggleAmbassador(User $user): RedirectResponse
    {
        $oldValue = $user->is_ambassador;
        $user->forceFill([
            'is_ambassador'   => ! $user->is_ambassador,
            'ambassador_tier' => ! $user->is_ambassador ? ($user->ambassador_tier ?? 'bronze') : $user->ambassador_tier,
        ])->save();

        AuditLogService::log($user->is_ambassador ? 'promote_ambassador' : 'demote_ambassador', 'User', $user->id,
            ['is_ambassador' => $oldValue],
            ['is_ambassador' => $user->is_ambassador]
        );

        $status = $user->is_ambassador ? 'promu Ambassadeur' : 'retire du programme Ambassadeur';

        return back()->with('success', "{$user->name} a ete {$status}.");
    }

    /**
     * Credit manuellement le wallet d'un utilisateur.
     * Utilise pour corriger un depot non credite (verifie par l'admin via le numero de transaction).
     */
    public function creditWallet(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'amount'            => ['required', 'numeric', 'min:100', 'max:10000000'],
            'transaction_ref'   => ['required', 'string', 'max:255'],
            'reason'            => ['required', 'string', 'max:1000'],
        ]);

        $amount = (float) $validated['amount'];

        DB::transaction(function () use ($user, $amount, $validated) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (! $wallet) {
                $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0, 'pending_balance' => 0, 'escrow_balance' => 0]);
            }

            $wallet->increment('balance', $amount);

            Transaction::create([
                'user_id'         => $user->id,
                'type'            => 'deposit',
                'amount_target'   => $amount,
                'gateway_fee'     => 0,
                'mantota_markup'  => 0,
                'amount_total'    => $amount,
                'status'          => 'completed',
                'reference'       => 'ADMIN-CREDIT-' . strtoupper(uniqid()),
                'gateway_ref'     => $validated['transaction_ref'],
                'payment_gateway' => 'admin_manual',
                'description'     => 'Credit manuel par admin : ' . $validated['reason'],
            ]);
        });

        AuditLogService::log('credit_wallet', 'User', $user->id,
            [],
            ['amount' => $amount, 'transaction_ref' => $validated['transaction_ref'], 'reason' => $validated['reason']]
        );

        return back()->with('success', number_format($amount, 0, ',', '.') . ' FCFA credites au portefeuille de ' . $user->name . '.');
    }

    /**
     * Envoie un email individuel a un utilisateur depuis le panel admin.
     */
    public function sendEmail(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string', 'max:10000'],
        ]);

        Mail::send([], [], function ($message) use ($user, $validated) {
            $message->to($user->email, $user->name)
                ->subject($validated['subject'])
                ->html(
                    '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:20px;">'
                    . '<h2 style="color:#0d9488;">MANTOTA</h2>'
                    . '<p>Bonjour <strong>' . e($user->name) . '</strong>,</p>'
                    . '<div style="margin:16px 0;padding:16px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">'
                    . nl2br(e($validated['body']))
                    . '</div>'
                    . '<p style="color:#94a3b8;font-size:12px;">Cet email a ete envoye par l\'equipe MANTOTA.</p>'
                    . '</div>'
                );
        });

        AuditLogService::log('send_email', 'User', $user->id,
            [],
            ['subject' => $validated['subject'], 'body_length' => strlen($validated['body'])]
        );

        return back()->with('success', 'Email envoye a ' . $user->name . ' (' . $user->email . ').');
    }

    // ── RBAC : Creation d'administrateurs ──

    /**
     * Super Admin cree un sous-admin avec des permissions granulaires.
     */
    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', \Illuminate\Validation\Rules\Password::defaults()],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::in([
                'super_admin',
                'manage_users',
                'manage_finance',
                'manage_kyc',
                'manage_disputes',
                'manage_campaigns',
                'manage_settings',
            ])],
        ]);

        $admin = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => $validated['password'],
            'role'              => UserRole::ADMIN,
        ]);
        $admin->forceFill(['admin_permissions' => $validated['permissions']])->save();

        Wallet::create(['user_id' => $admin->id, 'balance' => 0, 'pending_balance' => 0, 'escrow_balance' => 0]);

        AuditLogService::log('create_admin', 'User', $admin->id, [], [
            'name' => $admin->name,
            'email' => $admin->email,
            'permissions' => $validated['permissions'],
        ]);

        return back()->with('success', "Administrateur {$admin->name} cree avec succes.");
    }

    /**
     * Supprime un sous-admin (jamais le super_admin lui-meme).
     */
    public function destroyAdmin(User $user): RedirectResponse
    {
        $currentAdmin = auth('admin')->user();

        if ($user->id === $currentAdmin->id) {
            return back()->with('error', 'Impossible de supprimer votre propre compte.');
        }

        if ($user->role !== UserRole::ADMIN) {
            return back()->with('error', 'Cet utilisateur n\'est pas un administrateur.');
        }

        $name = $user->name;

        AuditLogService::log('delete_admin', 'User', $user->id,
            ['name' => $name, 'email' => $user->email],
            []
        );

        $user->wallet?->delete();
        $user->delete();

        return back()->with('success', "Administrateur {$name} supprime.");
    }

    public function export(): StreamedResponse
    {
        $users = User::leftJoin('wallets', 'users.id', '=', 'wallets.user_id')
            ->select('users.*', 'wallets.balance as wallet_balance')
            ->orderByDesc('users.created_at')
            ->get();

        $filename = 'utilisateurs_' . date('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID', 'Nom', 'Email', 'Role', 'Pays', 'Telephone',
                'KYC', 'VIP', 'Banni', 'Solde', 'Date inscription',
            ], ';');

            foreach ($users as $u) {
                fputcsv($handle, [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->role?->value ?? $u->role,
                    $u->country ?? '-',
                    $u->phone ?? '-',
                    $u->kyc_status ?? '-',
                    $u->is_vip ? 'Oui' : 'Non',
                    $u->is_banned ? 'Oui' : 'Non',
                    number_format((float) ($u->wallet_balance ?? 0), 0, ',', ' '),
                    $u->created_at?->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
