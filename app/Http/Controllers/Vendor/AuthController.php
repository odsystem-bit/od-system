<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewUserRegisteredAdminNotification;
use App\Notifications\WelcomeNotification;
use App\Services\SecurityService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * AuthController — Authentification dediee aux vendeurs MANTOTA.
 *
 * Completement separe du flux admin et créateur de contenu.
 * Seuls les comptes ayant le role UserRole::VENDOR peuvent se connecter.
 */
class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion vendeur.
     */
    public function showLogin(): InertiaResponse|RedirectResponse
    {
        if (Auth::guard('vendor')->check() && Auth::guard('vendor')->user()->role === UserRole::VENDOR) {
            return redirect()->route('vendor.dashboard');
        }

        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Authentifie un vendeur.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('vendor')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            SecurityService::log('login_failed', email: $request->input('email'), guard: 'vendor');

            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        $user = Auth::guard('vendor')->user();

        if ($user->role !== UserRole::VENDOR) {
            Auth::guard('vendor')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Acces refuse. Ce portail est reserve aux vendeurs.',
            ]);
        }

        if ($user->is_banned) {
            Auth::guard('vendor')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Votre compte a ete suspendu par l\'administration.',
            ]);
        }

        $request->session()->regenerate();

        SecurityService::log('login_success', $user->id, $user->email, 'vendor');

        return redirect()->route('vendor.dashboard');
    }

    /**
     * Affiche le formulaire d'inscription vendeur.
     */
    public function showRegister(): InertiaResponse|RedirectResponse
    {
        if (Auth::guard('vendor')->check() && Auth::guard('vendor')->user()->role === UserRole::VENDOR) {
            return redirect()->route('vendor.dashboard');
        }

        return Inertia::render('Auth/Register');
    }

    /**
     * Inscrit un nouveau vendeur.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'country'  => ['required', 'string', 'max:100'],
            'phone'    => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'country.required' => 'Le pays est obligatoire.',
            'phone.required'   => 'Le numero de telephone est obligatoire.',
        ]);

        $user = DB::transaction(function () use ($request) {
            // Generate unique referral code
            do {
                $code = Str::upper(Str::random(8));
            } while (User::where('referral_code', $code)->exists());

            $data = [
                'name'          => $request->name,
                'email'         => $request->email,
                'country'       => $request->country,
                'phone'         => $request->phone,
                'password'      => $request->password,
                'role'          => UserRole::VENDOR,
                'referral_code' => $code,
            ];

            // Link referrer if valid ref code provided
            if ($ref = $request->input('ref')) {
                $referrer = User::where('referral_code', $ref)->first();
                if ($referrer) {
                    $data['referred_by'] = $referrer->id;
                }
            }

            $user = User::create($data);

            Wallet::create([
                'user_id'         => $user->id,
                'balance'         => 0,
                'pending_balance' => 0,
            ]);

            // Credit referrer
            if (isset($referrer)) {
                $bonus = (int) \App\Models\Setting::get('referral_bonus_amount', '500');
                if ($bonus > 0) {
                    $referrer->increment('referral_count');
                    $referrer->increment('referral_earnings', $bonus);
                    $referrer->wallet?->increment('referral_balance', $bonus);

                    \App\Models\Transaction::create([
                        'user_id'        => $referrer->id,
                        'type'           => 'referral_bonus',
                        'amount_target'  => $bonus,
                        'amount_total'   => $bonus,
                        'gateway_fee'    => 0,
                        'mantota_markup' => 0,
                        'status'         => 'completed',
                        'reference'      => 'REF-' . uniqid('', true),
                        'description'    => "Bonus parrainage : {$user->name} s'est inscrit avec votre code.",
                    ]);
                }
            }

            return $user;
        });

        event(new Registered($user));

        // Email de bienvenue au vendeur
        $user->notify(new WelcomeNotification());

        // Notifier tous les admins du nouvel inscrit
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewUserRegisteredAdminNotification($user));
        }

        Auth::guard('vendor')->login($user);

        return redirect()->route('vendor.dashboard');
    }

    /**
     * Deconnecte le vendeur.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('vendor')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login');
    }

    /**
     * Affiche le formulaire de demande de reinitialisation de mot de passe.
     */
    public function showForgotPassword(): InertiaResponse
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Envoie le lien de reinitialisation de mot de passe.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::broker('vendors')->sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    /**
     * Affiche le formulaire de reinitialisation de mot de passe.
     */
    public function showResetForm(Request $request, string $token): InertiaResponse
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reinitialise le mot de passe.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::broker('vendors')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('vendor.login')->with('status', __($status));
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
