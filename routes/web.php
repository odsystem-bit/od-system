<?php

use App\Http\Controllers\Influencer\CampaignController as InfluencerCampaignController;
use App\Http\Controllers\Influencer\DashboardController as InfluencerDashboardController;
use App\Http\Controllers\Influencer\ProfileController as InfluencerProfileController;
use App\Http\Controllers\Influencer\ServiceController as InfluencerServiceController;
use App\Http\Controllers\Influencer\ServiceOrderController as InfluencerServiceOrderController;
use App\Http\Controllers\Influencer\ServiceOrderMessageController as InfluencerServiceOrderMessageController;
use App\Http\Controllers\Influencer\WalletController as InfluencerWalletController;
use App\Http\Controllers\Influencer\KYCController as InfluencerKYCController;
use App\Http\Controllers\Influencer\SupportController as InfluencerSupportController;
use App\Http\Controllers\Payment\PayDunyaController;
use App\Http\Controllers\Payment\DepositController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\OrderTrackingController;
use App\Http\Controllers\Public\PublicDisputeController;
use App\Http\Controllers\Public\PublicInfluencerController;
use App\Http\Controllers\Public\ShopController;
use App\Http\Controllers\Public\SmartLinkController;
use App\Http\Controllers\Vendor\CampaignController as VendorCampaignController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\KYCController as VendorKYCController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Vendor\ServiceOrderController as VendorServiceOrderController;
use App\Http\Controllers\Vendor\ServiceOrderMessageController as VendorServiceOrderMessageController;
use App\Http\Controllers\Vendor\WalletController as VendorWalletController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\SupportController as VendorSupportController;
use App\Http\Controllers\Vendor\InfluencerProfileController as VendorInfluencerProfileController;
use App\Http\Controllers\Vendor\SettingsController as VendorSettingsController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DisputeController as AdminDisputeController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\ImpersonationController as AdminImpersonationController;
use App\Http\Controllers\Admin\ModerationController as AdminModerationController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SystemCampaignController as AdminSystemCampaignController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\KYCController as AdminKYCController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Admin\GatewayController as AdminGatewayController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Admin\ServiceOrderChatController as AdminServiceOrderChatController;
use App\Http\Controllers\Admin\ServiceOrderController as AdminServiceOrderController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\FlaggedMessageController as AdminFlaggedMessageController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminEmailController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Vendor\AuthController as VendorAuthController;
use App\Http\Controllers\Webhook\FeexPayWebhookController;
use App\Http\Controllers\Webhook\PayDunyaWebhookController;
use App\Http\Controllers\Influencer\AuthController as InfluencerAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// SEO : Sitemap dynamique
Route::get('/sitemap.xml', [App\Http\Controllers\Public\SitemapController::class, 'index'])->name('sitemap');

// Smart redirect : redirige vers le bon portail selon le guard authentifie
Route::get('/dashboard', function () {
    if (auth('admin')->check())      return redirect()->route('admin.dashboard');
    if (auth('vendor')->check())     return redirect()->route('vendor.dashboard');
    if (auth('influencer')->check()) return redirect()->route('influencer.dashboard');
    return redirect()->route('home');
})->name('dashboard');

// ──────────────────────────────────────────────
//  Routes Createur de contenu — Authentification separee
// ──────────────────────────────────────────────
Route::prefix('influencer')->name('influencer.')->group(function () {

    // Login createur de contenu (accessible sans authentification createur de contenu)
    Route::middleware('guest:influencer')->group(function () {
        Route::get('/login', [InfluencerAuthController::class, 'showLogin'])
            ->name('login');

        Route::post('/login', [InfluencerAuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.store');

        Route::get('/register', [InfluencerAuthController::class, 'showRegister'])
            ->name('register');

        Route::post('/register', [InfluencerAuthController::class, 'register'])
            ->middleware('throttle:5,1')
            ->name('register.store');

        Route::get('/forgot-password', [InfluencerAuthController::class, 'showForgotPassword'])
            ->name('password.request');

        Route::post('/forgot-password', [InfluencerAuthController::class, 'sendResetLink'])
            ->name('password.email');

        Route::get('/reset-password/{token}', [InfluencerAuthController::class, 'showResetForm'])
            ->name('password.reset');

        Route::post('/reset-password', [InfluencerAuthController::class, 'resetPassword'])
            ->name('password.store');
    });

    // Logout createur de contenu
    Route::post('/logout', [InfluencerAuthController::class, 'logout'])
        ->middleware('auth:influencer')
        ->name('logout');

    // Verification email createur de contenu
    Route::middleware('auth:influencer')->group(function () {
        Route::get('/verify-email', function (\Illuminate\Http\Request $request) {
            return $request->user()->hasVerifiedEmail()
                ? redirect()->intended(route('influencer.dashboard'))
                : \Inertia\Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
        })->name('verification.notice');

        Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
            if ($request->user()->hasVerifiedEmail()) {
                return redirect()->intended(route('influencer.dashboard'));
            }
            $request->user()->sendEmailVerificationNotification();
            return back()->with('status', 'verification-code-sent');
        })->middleware('throttle:6,1')->name('verification.send');

        Route::post('/verify-email-code', function (\Illuminate\Http\Request $request) {
            $request->validate(['code' => ['required', 'string', 'size:6']]);
            $user = $request->user();
            if ($user->hasVerifiedEmail()) {
                return redirect()->intended(route('influencer.dashboard') . '?verified=1');
            }
            if (
                $user->email_verification_code !== $request->code
                || !$user->email_verification_code_expires_at
                || $user->email_verification_code_expires_at->isPast()
            ) {
                return back()->withErrors(['code' => 'Le code est invalide ou a expiré.']);
            }
            $user->markEmailAsVerified();
            event(new \Illuminate\Auth\Events\Verified($user));
            $user->forceFill(['email_verification_code' => null, 'email_verification_code_expires_at' => null])->save();
            return redirect()->intended(route('influencer.dashboard') . '?verified=1');
        })->middleware('throttle:6,1')->name('verification.verify');
    });
});

// Routes protegees createur de contenu — role:influencer
Route::middleware(['auth:influencer', 'role:influencer'])
    ->prefix('influencer')
    ->name('influencer.')
    ->group(function () {

        // Dashboard — campagnes disponibles
        Route::get('/dashboard', [InfluencerDashboardController::class, 'index'])
            ->middleware('verified:influencer.verification.notice')
            ->name('dashboard');

        // Mes liens generes
        Route::get('/links', [InfluencerDashboardController::class, 'myLinks'])
            ->name('links');

        // Campagnes — generation de lien & soumission de preuve
        Route::post('/campaigns/{campaign}/generate-link', [InfluencerCampaignController::class, 'generateLink'])
            ->middleware('verified:influencer.verification.notice')
            ->name('campaigns.generate-link');

        // Portefeuille & retraits
        Route::get('/wallet', [InfluencerWalletController::class, 'index'])
            ->name('wallet.index');

        Route::post('/wallet/withdraw', [InfluencerWalletController::class, 'requestWithdrawal'])
            ->middleware(['verified:influencer.verification.notice', 'throttle:3,5'])
            ->name('wallet.withdraw');

        Route::post('/wallet/transfer-referral', [InfluencerWalletController::class, 'transferReferralBalance'])
            ->middleware('throttle:3,5')
            ->name('wallet.transfer-referral');

        Route::post('/wallet/purchase-badge', [InfluencerWalletController::class, 'purchaseBadge'])
            ->middleware('throttle:3,5')
            ->name('wallet.purchase-badge');

        // Depot (passerelle active : PayDunya ou FeexPay)
        Route::post('/deposit', DepositController::class)
            ->middleware(['verified:influencer.verification.notice', 'throttle:5,5'])
            ->name('deposit');

        Route::get('/deposit/status/{transaction}', [DepositController::class, 'checkStatus'])
            ->name('deposit.status');

        Route::get('/deposit/callback', function (\Illuminate\Http\Request $request) {
            $pdyToken = $request->query('token');
            if ($pdyToken) {
                $tx = \App\Models\Transaction::where('gateway_ref', (string) $pdyToken)
                    ->where('status', 'pending')
                    ->first();
                if ($tx) {
                    DepositController::verifyAndCreditPayDunya($tx);
                }
            }

            $feexTxnId = $request->query('transaction_id');
            if ($feexTxnId && ! $pdyToken) {
                $tx = \App\Models\Transaction::where('id', (int) $feexTxnId)
                    ->where('status', 'pending')
                    ->first();
                if ($tx) {
                    DepositController::verifyAndCreditFeexPay($tx);
                }
            }

            return redirect()->route('influencer.dashboard');
        })->name('deposit.callback');

        // Profil social — declaration des reseaux
        Route::get('/profile', [InfluencerProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::put('/profile/socials', [InfluencerProfileController::class, 'updateSocials'])
            ->name('profile.socials.update');

        Route::post('/profile/photo', [InfluencerProfileController::class, 'updatePhoto'])
            ->name('profile.photo.update');

        // KYC Createur de contenu
        Route::get('/kyc', [InfluencerKYCController::class, 'index'])
            ->name('kyc.index');

        Route::post('/kyc', [InfluencerKYCController::class, 'store'])
            ->name('kyc.store');

        Route::post('/vip-request', [InfluencerKYCController::class, 'requestVip'])
            ->name('vip.request');

        // MANTOTA Studios — Services VIP
        Route::resource('services', InfluencerServiceController::class)
            ->only(['index', 'store', 'edit', 'update', 'destroy']);

        // Commandes de services recues
        Route::get('/service-orders', [InfluencerServiceOrderController::class, 'index'])
            ->name('service-orders.index');

        Route::get('/service-orders/{order}', [InfluencerServiceOrderController::class, 'show'])
            ->name('service-orders.show');

        Route::patch('/service-orders/{order}/accept', [InfluencerServiceOrderController::class, 'accept'])
            ->name('service-orders.accept');

        Route::post('/service-orders/{order}/deliver', [InfluencerServiceOrderController::class, 'deliver'])
            ->name('service-orders.deliver');

        Route::patch('/service-orders/{order}/sample-received', [InfluencerServiceOrderController::class, 'markSampleReceived'])
            ->name('service-orders.sample-received');

        Route::patch('/service-orders/{order}/cancel', [InfluencerServiceOrderController::class, 'cancel'])
            ->name('service-orders.cancel');

        Route::post('/service-orders/{order}/messages', [InfluencerServiceOrderMessageController::class, 'store'])
            ->name('service-orders.messages.store');

        // Support Createur de contenu
        Route::get('/support', [InfluencerSupportController::class, 'index'])
            ->name('support.index');
        Route::get('/support/create', [InfluencerSupportController::class, 'create'])
            ->name('support.create');
        Route::post('/support', [InfluencerSupportController::class, 'store'])
            ->name('support.store');
        Route::get('/support/{ticket}', [InfluencerSupportController::class, 'show'])
            ->name('support.show');
        Route::post('/support/{ticket}/reply', [InfluencerSupportController::class, 'reply'])
            ->name('support.reply');

        // Notifications
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');

        // Suppression de compte
        Route::delete('/account', [AccountController::class, 'destroy'])
            ->name('account.destroy');
        // Dismiss welcome popup
        Route::post('/welcome-popup-dismiss', function () {
            auth('influencer')->user()->update(['welcome_popup_seen' => true]);
            return back();
        })->name('welcome-popup.dismiss');    });

// ──────────────────────────────────────────────
//  Routes Vendeur — Authentification separee
// ──────────────────────────────────────────────
Route::prefix('vendor')->name('vendor.')->group(function () {

    // Login vendeur (accessible sans authentification vendeur)
    Route::middleware('guest:vendor')->group(function () {
        Route::get('/login', [VendorAuthController::class, 'showLogin'])
            ->name('login');

        Route::post('/login', [VendorAuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.store');

        Route::get('/register', [VendorAuthController::class, 'showRegister'])
            ->name('register');

        Route::post('/register', [VendorAuthController::class, 'register'])
            ->middleware('throttle:5,1')
            ->name('register.store');

        Route::get('/forgot-password', [VendorAuthController::class, 'showForgotPassword'])
            ->name('password.request');

        Route::post('/forgot-password', [VendorAuthController::class, 'sendResetLink'])
            ->name('password.email');

        Route::get('/reset-password/{token}', [VendorAuthController::class, 'showResetForm'])
            ->name('password.reset');

        Route::post('/reset-password', [VendorAuthController::class, 'resetPassword'])
            ->name('password.store');
    });

    // Logout vendeur
    Route::post('/logout', [VendorAuthController::class, 'logout'])
        ->middleware('auth:vendor')
        ->name('logout');

    // Verification email vendeur
    Route::middleware('auth:vendor')->group(function () {
        Route::get('/verify-email', function (\Illuminate\Http\Request $request) {
            return $request->user()->hasVerifiedEmail()
                ? redirect()->intended(route('vendor.dashboard'))
                : \Inertia\Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
        })->name('verification.notice');

        Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
            if ($request->user()->hasVerifiedEmail()) {
                return redirect()->intended(route('vendor.dashboard'));
            }
            $request->user()->sendEmailVerificationNotification();
            return back()->with('status', 'verification-code-sent');
        })->middleware('throttle:6,1')->name('verification.send');

        Route::post('/verify-email-code', function (\Illuminate\Http\Request $request) {
            $request->validate(['code' => ['required', 'string', 'size:6']]);
            $user = $request->user();
            if ($user->hasVerifiedEmail()) {
                return redirect()->intended(route('vendor.dashboard') . '?verified=1');
            }
            if (
                $user->email_verification_code !== $request->code
                || !$user->email_verification_code_expires_at
                || $user->email_verification_code_expires_at->isPast()
            ) {
                return back()->withErrors(['code' => 'Le code est invalide ou a expiré.']);
            }
            $user->markEmailAsVerified();
            event(new \Illuminate\Auth\Events\Verified($user));
            $user->forceFill(['email_verification_code' => null, 'email_verification_code_expires_at' => null])->save();
            return redirect()->intended(route('vendor.dashboard') . '?verified=1');
        })->middleware('throttle:6,1')->name('verification.verify');
    });
});

// Routes protegees vendeur — role:vendor
Route::middleware(['auth:vendor', 'role:vendor'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [VendorDashboardController::class, 'index'])
            ->middleware('verified:vendor.verification.notice')
            ->name('dashboard');

        // Analytics
        Route::get('/analytics', [\App\Http\Controllers\Vendor\AnalyticsController::class, 'index'])
            ->name('analytics');

        // Portefeuille
        Route::get('/wallet', [VendorWalletController::class, 'index'])
            ->name('wallet.index');
        Route::get('/kyc', [VendorKYCController::class, 'index'])
            ->name('kyc.index');

        Route::post('/kyc', [VendorKYCController::class, 'store'])
            ->name('kyc.store');

        // Campagnes — CRUD complet
        Route::get('/campaigns', [VendorCampaignController::class, 'index'])
            ->name('campaigns.index');

        Route::get('/campaigns/create', [VendorCampaignController::class, 'create'])
            ->middleware('verified:vendor.verification.notice')
            ->name('campaigns.create');

        Route::post('/campaigns', [VendorCampaignController::class, 'store'])
            ->middleware('verified:vendor.verification.notice')
            ->name('campaigns.store');

        Route::get('/campaigns/{campaign}', [VendorCampaignController::class, 'show'])
            ->name('campaigns.show');

        Route::get('/campaigns/{campaign}/edit', [VendorCampaignController::class, 'edit'])
            ->name('campaigns.edit');

        Route::put('/campaigns/{campaign}', [VendorCampaignController::class, 'update'])
            ->name('campaigns.update');

        Route::post('/campaigns/{campaign}/toggle-pause', [VendorCampaignController::class, 'togglePause'])
            ->name('campaigns.toggle-pause');

        Route::post('/campaigns/{campaign}/add-budget', [VendorCampaignController::class, 'addBudget'])
            ->name('campaigns.add-budget');

        Route::delete('/campaigns/{campaign}', [VendorCampaignController::class, 'destroy'])
            ->name('campaigns.destroy');

        // Produits — CRUD complet
        Route::resource('products', VendorProductController::class);

        // Format d'affichage boutique
        Route::put('/products-display-format', [VendorProductController::class, 'updateShopFormat'])
            ->name('products.display-format');

        // MANTOTA Studios — Catalogue des services
        Route::get('/studios', [VendorServiceOrderController::class, 'catalog'])
            ->name('studios.index');

        // Commandes de services — Studios
        Route::get('/service-orders', [VendorServiceOrderController::class, 'index'])
            ->name('service-orders.index');

        Route::get('/service-orders/create/{serviceId?}', [VendorServiceOrderController::class, 'create'])
            ->name('service-orders.create');

        Route::post('/service-orders', [VendorServiceOrderController::class, 'store'])
            ->name('service-orders.store');

        Route::get('/service-orders/{order}', [VendorServiceOrderController::class, 'show'])
            ->name('service-orders.show');

        Route::post('/service-orders/{order}/approve', [VendorServiceOrderController::class, 'approve'])
            ->name('service-orders.approve');

        Route::post('/service-orders/{order}/revision', [VendorServiceOrderController::class, 'requestRevision'])
            ->name('service-orders.revision');

        Route::post('/service-orders/{order}/dispute', [VendorServiceOrderController::class, 'dispute'])
            ->name('service-orders.dispute');

        Route::post('/service-orders/{order}/reject', [VendorServiceOrderController::class, 'reject'])
            ->name('service-orders.reject');

        Route::post('/service-orders/{order}/cancel', [VendorServiceOrderController::class, 'cancel'])
            ->name('service-orders.cancel');

        Route::post('/service-orders/{order}/ship-sample', [VendorServiceOrderController::class, 'markSampleShipped'])
            ->name('service-orders.ship-sample');

        Route::post('/service-orders/{order}/messages', [VendorServiceOrderMessageController::class, 'store'])
            ->name('service-orders.messages.store');

        // Commandes e-commerce — Guest Checkout
        Route::get('/orders', [VendorOrderController::class, 'index'])
            ->name('orders.index');

        Route::get('/orders/{order}', [VendorOrderController::class, 'show'])
            ->name('orders.show');

        Route::post('/orders/{order}/ship', [VendorOrderController::class, 'markShipped'])
            ->name('orders.ship');

        Route::post('/orders/{order}/cancel', [VendorOrderController::class, 'cancel'])
            ->name('orders.cancel');

        Route::post('/orders/{order}/defense', [VendorOrderController::class, 'submitDefense'])
            ->name('orders.defense');

        // Chat litige e-commerce (vendeur)
        Route::get('/orders/{order}/dispute-chat', [VendorOrderController::class, 'disputeChat'])
            ->name('orders.dispute-chat');

        Route::post('/orders/{order}/dispute-chat', [VendorOrderController::class, 'storeDisputeMessage'])
            ->name('orders.dispute-chat.store');

        // Profil createur de contenu (vue vendeur)
        Route::get('/influencers/{user}', [VendorInfluencerProfileController::class, 'show'])
            ->name('influencer.show');

        // Retrait vendeur
        Route::post('/wallet/withdraw', [VendorWalletController::class, 'withdraw'])
            ->middleware(['verified:vendor.verification.notice', 'throttle:3,5'])
            ->name('wallet.withdraw');

        // Achat badge Meilleure Boutique
        Route::post('/wallet/purchase-badge', [VendorWalletController::class, 'purchaseBadge'])
            ->middleware('throttle:3,5')
            ->name('wallet.purchase-badge');

        // Depot (passerelle active : PayDunya ou FeexPay)
        Route::post('/deposit', DepositController::class)
            ->middleware(['verified:vendor.verification.notice', 'throttle:5,5'])
            ->name('deposit');

        Route::get('/deposit/status/{transaction}', [DepositController::class, 'checkStatus'])
            ->name('deposit.status');

        Route::get('/deposit/callback', function (\Illuminate\Http\Request $request) {
            // La passerelle redirige le navigateur ici après paiement.
            // On tente de vérifier et créditer avant de rediriger au dashboard.

            // PayDunya envoie ?token={invoice_token}
            $pdyToken = $request->query('token');
            if ($pdyToken) {
                $tx = \App\Models\Transaction::where('gateway_ref', (string) $pdyToken)
                    ->where('status', 'pending')
                    ->first();
                if ($tx) {
                    DepositController::verifyAndCreditPayDunya($tx);
                }
            }

            // FeexPay redirige avec ?transaction_id= (handled via polling)
            $feexTxnId = $request->query('transaction_id');
            if ($feexTxnId && ! $pdyToken) {
                $tx = \App\Models\Transaction::where('id', (int) $feexTxnId)
                    ->where('status', 'pending')
                    ->first();
                if ($tx) {
                    DepositController::verifyAndCreditFeexPay($tx);
                }
            }

            return redirect()->route('vendor.dashboard');
        })->name('deposit.callback');

        // Support Vendeur
        Route::get('/support', [VendorSupportController::class, 'index'])
            ->name('support.index');
        Route::get('/support/create', [VendorSupportController::class, 'create'])
            ->name('support.create');
        Route::post('/support', [VendorSupportController::class, 'store'])
            ->name('support.store');
        Route::get('/support/{ticket}', [VendorSupportController::class, 'show'])
            ->name('support.show');
        Route::post('/support/{ticket}/reply', [VendorSupportController::class, 'reply'])
            ->name('support.reply');

        // Notifications
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');

        // Parametres
        Route::get('/settings', [VendorSettingsController::class, 'index'])
            ->name('settings');
        Route::post('/settings/branding', [VendorSettingsController::class, 'updateBranding'])
            ->name('settings.branding');
        Route::post('/settings/theme', [VendorSettingsController::class, 'updateTheme'])
            ->name('settings.theme');

        // Suppression de compte
        Route::delete('/account', [AccountController::class, 'destroy'])
            ->name('account.destroy');

        // Dismiss welcome popup
        Route::post('/welcome-popup-dismiss', function () {
            auth('vendor')->user()->update(['welcome_popup_seen' => true]);
            return back();
        })->name('welcome-popup.dismiss');
    });

// ──────────────────────────────────────────────
//  Profil Public Createur de contenu — Aucune auth
// ──────────────────────────────────────────────
Route::get('/influencer/{username}', [PublicInfluencerController::class, 'show'])
    ->name('influencer.public-profile');

// ──────────────────────────────────────────────
//  Pipeline CPC — SmartLink anti-fraude (fingerprint)
// ──────────────────────────────────────────────
Route::get('/go/{hash}', [SmartLinkController::class, 'gate'])
    ->middleware('throttle:30,1')
    ->name('smartlink.redirect');

Route::post('/go/{hash}/click', [SmartLinkController::class, 'processClick'])
    ->middleware('throttle:30,1')
    ->name('smartlink.click');

// ──────────────────────────────────────────────
//  Mini-site Boutique Publique — Aucune auth
// ──────────────────────────────────────────────
Route::get('/shop/{vendorSlug}', [ShopController::class, 'show'])
    ->name('shop.show');

Route::get('/shop/checkout/{product}', [CheckoutController::class, 'show'])
    ->name('shop.checkout.show');

Route::post('/shop/checkout/{product}', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('shop.checkout.store');

Route::get('/shop/checkout/{order}/success', [CheckoutController::class, 'success'])
    ->name('shop.checkout.success');

Route::get('/shop/checkout/{order}/payment-return', [CheckoutController::class, 'paymentReturn'])
    ->name('shop.checkout.payment-return');

Route::get('/shop/checkout/{order}/payment-status', [CheckoutController::class, 'checkPaymentStatus'])
    ->name('shop.checkout.payment-status');

// ──────────────────────────────────────────────
//  Suivi de commande public (Magic Link)
// ──────────────────────────────────────────────
Route::get('/order/lookup', [OrderTrackingController::class, 'lookup'])
    ->name('order.lookup');

Route::post('/order/lookup', [OrderTrackingController::class, 'lookupSubmit'])
    ->name('order.lookup.submit');

Route::get('/track/{order}', [OrderTrackingController::class, 'show'])
    ->name('order.track');

Route::post('/track/{order}/confirm', [OrderTrackingController::class, 'confirm'])
    ->middleware('throttle:5,1')
    ->name('order.track.confirm');

Route::post('/track/{order}/dispute', [OrderTrackingController::class, 'dispute'])
    ->middleware('throttle:3,5')
    ->name('order.track.dispute');

Route::get('/track/{order}/download', [OrderTrackingController::class, 'downloadDigital'])
    ->middleware('throttle:10,1')
    ->name('order.track.download');

// Chat litige public (acces via tracking_token)
Route::get('/dispute/{order}/chat', [PublicDisputeController::class, 'show'])
    ->name('public.dispute.chat');

Route::post('/dispute/{order}/chat', [PublicDisputeController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.dispute.chat.store');

// ──────────────────────────────────────────────
//  Webhooks PayDunya — Routes publiques (sans CSRF)
//  Verification via l'API Confirm PayDunya.
// ──────────────────────────────────────────────
Route::post('/webhooks/paydunya', PayDunyaWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.paydunya');

// ──────────────────────────────────────────────
//  Webhooks FeexPay — Routes publiques (sans CSRF)
// ──────────────────────────────────────────────
Route::post('/webhooks/feexpay', FeexPayWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhook.feexpay');

// ──────────────────────────────────────────────
//  Support (Helpdesk) — Routes publiques
// ──────────────────────────────────────────────
Route::get('/support', [App\Http\Controllers\Public\SupportController::class, 'create'])
    ->name('support.create');

Route::post('/support', [App\Http\Controllers\Public\SupportController::class, 'store'])
    ->middleware('throttle:3,5')
    ->name('support.store');

Route::get('/support/track', [App\Http\Controllers\Public\SupportController::class, 'track'])
    ->name('support.track');

Route::post('/support/track', [App\Http\Controllers\Public\SupportController::class, 'lookup'])
    ->name('support.lookup');

Route::get('/support/{reference}', [App\Http\Controllers\Public\SupportController::class, 'show'])
    ->name('support.show');

Route::post('/support/{reference}/reply', [App\Http\Controllers\Public\SupportController::class, 'reply'])
    ->middleware('throttle:10,1')
    ->name('support.reply');

// ──────────────────────────────────────────────
//  Pages legales (CGV, Confidentialite)
// ──────────────────────────────────────────────
Route::get('/conditions-generales', [App\Http\Controllers\Public\LegalController::class, 'terms'])
    ->name('legal.terms');

Route::get('/politique-confidentialite', [App\Http\Controllers\Public\LegalController::class, 'privacy'])
    ->name('legal.privacy');

// ──────────────────────────────────────────────
//  Pages publiques (Tarifs, A propos, Documentation)
// ──────────────────────────────────────────────
Route::get('/tarifs', [App\Http\Controllers\Public\PageController::class, 'tarifs'])
    ->name('tarifs');

Route::get('/a-propos', [App\Http\Controllers\Public\PageController::class, 'about'])
    ->name('about');

Route::get('/documentation', [App\Http\Controllers\Public\PageController::class, 'documentation'])
    ->name('documentation');

Route::get('/documentation/vendeur', [App\Http\Controllers\Public\PageController::class, 'docVendeur'])
    ->name('documentation.vendeur');

Route::get('/documentation/createur', [App\Http\Controllers\Public\PageController::class, 'docCreateur'])
    ->name('documentation.createur');

Route::get('/documentation/client', [App\Http\Controllers\Public\PageController::class, 'docClient'])
    ->name('documentation.client');

// ──────────────────────────────────────────────
//  Recuperation IP admin (hors middleware admin.ip)
// ──────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/recover-ip', [App\Http\Controllers\Admin\IpRecoveryController::class, 'show'])
        ->middleware('throttle:10,1')
        ->name('ip-recovery.show');
    Route::post('/recover-ip', [App\Http\Controllers\Admin\IpRecoveryController::class, 'recover'])
        ->middleware('throttle:5,1')
        ->name('ip-recovery.recover');
});

// ──────────────────────────────────────────────
//  Routes Administrateur — Authentification separee
// ──────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Login admin (accessible sans authentification admin)
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])
            ->name('login');

        Route::post('/login', [AdminAuthController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('login.store');
    });

    // Logout admin (accessible authentifie admin)
    Route::post('/logout', [AdminAuthController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');
});

// Routes protegees admin — role:admin
Route::middleware(['auth:admin', 'role:admin', 'admin.ip'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard — KPI globaux et alertes
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        // ── KYC & VIP (Pilier 3) ──
        Route::get('/kyc', [AdminKYCController::class, 'index'])
            ->name('kyc.index');

        Route::patch('/kyc/{user}/approve', [AdminModerationController::class, 'approveKyc'])
            ->name('kyc.approve');

        Route::patch('/kyc/{user}/reject', [AdminModerationController::class, 'rejectKyc'])
            ->name('kyc.reject');

        Route::patch('/vip/{user}/approve', [AdminModerationController::class, 'approveVip'])
            ->name('vip.approve');

        Route::patch('/vip/{user}/reject', [AdminModerationController::class, 'rejectVip'])
            ->name('vip.reject');

        // ── Retraits / Payouts (Pilier 4) ──
        Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])
            ->middleware('admin.permission:manage_finance')
            ->name('withdrawals.index');

        Route::get('/withdrawals/export', [AdminWithdrawalController::class, 'export'])
            ->middleware('admin.permission:manage_finance')
            ->name('withdrawals.export');

        Route::patch('/withdrawal/{transaction}/approve', [AdminModerationController::class, 'approveWithdrawal'])
            ->middleware('admin.permission:manage_finance')
            ->name('withdrawal.approve');

        Route::patch('/withdrawal/{transaction}/reject', [AdminModerationController::class, 'rejectWithdrawal'])
            ->middleware('admin.permission:manage_finance')
            ->name('withdrawal.reject');

        // ── Passerelles / Gateways (Pilier 7) ──
        Route::get('/gateways', [AdminGatewayController::class, 'index'])
            ->name('gateways.index');

        Route::put('/gateways/{gateway}', [AdminGatewayController::class, 'update'])
            ->name('gateways.update');

        // Gestion Utilisateurs
        Route::get('/users', [AdminUserController::class, 'index'])
            ->middleware('admin.permission:manage_users')
            ->name('users.index');

        Route::get('/users/export', [AdminUserController::class, 'export'])
            ->middleware('admin.permission:manage_users')
            ->name('users.export');

        Route::get('/users/{user}', [AdminUserController::class, 'show'])
            ->withTrashed()
            ->middleware('admin.permission:manage_users')
            ->name('users.show');

        Route::patch('/users/{user}/update-socials', [AdminUserController::class, 'updateSocials'])
            ->name('users.update-socials');

        Route::patch('/users/{user}/toggle-ban', [AdminUserController::class, 'toggleBan'])
            ->withTrashed()
            ->name('users.toggle-ban');

        Route::patch('/users/{user}/toggle-wallet-lock', [AdminUserController::class, 'toggleWalletLock'])
            ->name('users.toggle-wallet-lock');

        Route::patch('/users/{user}/toggle-ambassador', [AdminUserController::class, 'toggleAmbassador'])
            ->name('users.toggle-ambassador');

        Route::post('/users/{user}/credit-wallet', [AdminUserController::class, 'creditWallet'])
            ->middleware('admin.permission:manage_finance')
            ->name('users.credit-wallet');

        Route::post('/users/{user}/send-email', [AdminUserController::class, 'sendEmail'])
            ->middleware('admin.permission:manage_users')
            ->name('users.send-email');

        // RBAC — Gestion Admins
        Route::post('/users/admins', [AdminUserController::class, 'storeAdmin'])
            ->middleware('admin.permission:super_admin')
            ->name('users.store-admin');

        Route::delete('/users/admins/{user}', [AdminUserController::class, 'destroyAdmin'])
            ->middleware('admin.permission:super_admin')
            ->name('users.destroy-admin');

        // Ghost Mode (Impersonation)
        Route::post('/impersonate/{user}', [AdminImpersonationController::class, 'start'])
            ->whereNumber('user')
            ->middleware('admin.permission:super_admin')
            ->name('impersonate.start');

        // Parametres
        Route::get('/settings', [AdminSettingController::class, 'index'])
            ->middleware('admin.permission:manage_settings')
            ->name('settings.index');

        Route::put('/settings', [AdminSettingController::class, 'update'])
            ->middleware('admin.permission:manage_settings')
            ->name('settings.update');

        Route::post('/settings/logo', [AdminSettingController::class, 'uploadLogo'])
            ->middleware('admin.permission:manage_settings')
            ->name('settings.logo');

        // Temoignages
        Route::get('/testimonials', [AdminTestimonialController::class, 'index'])
            ->name('testimonials.index');
        Route::post('/testimonials', [AdminTestimonialController::class, 'store'])
            ->name('testimonials.store');
        Route::put('/testimonials/{testimonial}', [AdminTestimonialController::class, 'update'])
            ->name('testimonials.update');
        Route::delete('/testimonials/{testimonial}', [AdminTestimonialController::class, 'destroy'])
            ->name('testimonials.destroy');

        // Emails de masse
        Route::get('/emails', [AdminEmailController::class, 'index'])
            ->name('emails.index');
        Route::post('/emails/send', [AdminEmailController::class, 'send'])
            ->middleware('throttle:5,1')
            ->name('emails.send');

        // Abonnements Ambassadeur
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])
            ->name('subscriptions.index');
        Route::post('/subscriptions/{user}/extend', [AdminSubscriptionController::class, 'extend'])
            ->name('subscriptions.extend');
        Route::post('/subscriptions/{user}/revoke', [AdminSubscriptionController::class, 'revoke'])
            ->name('subscriptions.revoke');

        // Litiges (Tribunal)
        Route::get('/disputes', [AdminDisputeController::class, 'index'])
            ->name('disputes.index');

        Route::get('/disputes/{order}', [AdminDisputeController::class, 'show'])
            ->name('disputes.show');

        Route::patch('/disputes/{order}/refund', [AdminDisputeController::class, 'refundClient'])
            ->name('disputes.refund');

        Route::patch('/disputes/{order}/favor-vendor', [AdminDisputeController::class, 'favorVendor'])
            ->name('disputes.favor-vendor');

        // Chat litige e-commerce (admin — tribunal)
        Route::get('/disputes/{order}/chat', [AdminDisputeController::class, 'chat'])
            ->name('disputes.ecommerce-chat');

        Route::post('/disputes/{order}/chat', [AdminDisputeController::class, 'storeMessage'])
            ->name('disputes.ecommerce-chat.store');

        // Litiges UGC / Studios
        Route::patch('/disputes/service/{serviceOrder}/refund-vendor', [AdminDisputeController::class, 'refundVendorService'])
            ->name('disputes.service.refund-vendor');

        Route::patch('/disputes/service/{serviceOrder}/favor-influencer', [AdminDisputeController::class, 'favorInfluencerService'])
            ->name('disputes.service.favor-influencer');

        // Chat Admin — Tour de controle & Intervention
        Route::get('/disputes/service/{serviceOrder}/chat', [AdminServiceOrderChatController::class, 'show'])
            ->name('disputes.chat.show');

        Route::post('/disputes/service/{serviceOrder}/chat', [AdminServiceOrderChatController::class, 'store'])
            ->name('disputes.chat.store');

        // Omniscience Admin — Vue lecture seule d'une commande UGC
        Route::get('/service-orders/{serviceOrder}', [AdminServiceOrderController::class, 'show'])
            ->name('service-orders.show');

        // Campagnes Systeme (God Mode) — CRUD complet
        Route::get('/campaigns', [AdminSystemCampaignController::class, 'index'])
            ->name('campaigns.index');

        Route::get('/campaigns/create', [AdminSystemCampaignController::class, 'create'])
            ->name('campaigns.create');

        Route::post('/campaigns', [AdminSystemCampaignController::class, 'store'])
            ->name('campaigns.store');

        Route::get('/campaigns/{campaign}/edit', [AdminSystemCampaignController::class, 'edit'])
            ->name('campaigns.edit');

        Route::get('/campaigns/{campaign}', [AdminSystemCampaignController::class, 'show'])
            ->name('campaigns.show');

        Route::put('/campaigns/{campaign}', [AdminSystemCampaignController::class, 'update'])
            ->name('campaigns.update');

        Route::post('/campaigns/{campaign}/toggle-pause', [AdminSystemCampaignController::class, 'togglePause'])
            ->name('campaigns.toggle-pause');

        Route::delete('/campaigns/{campaign}', [AdminSystemCampaignController::class, 'destroy'])
            ->name('campaigns.destroy');

        // Audit Log (Boite Noire) — Tracabilite admin
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])
            ->name('audit-logs.index');

        // Commandes globales (Omniscience)
        Route::get('/orders', [AdminOrderController::class, 'index'])
            ->name('orders.index');

        Route::get('/orders/export', [AdminOrderController::class, 'export'])
            ->name('orders.export');

        // Annonces (Megaphone)
        Route::get('/announcements', [AdminAnnouncementController::class, 'index'])
            ->name('announcements.index');
        Route::post('/announcements', [AdminAnnouncementController::class, 'store'])
            ->name('announcements.store');
        Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])
            ->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])
            ->name('announcements.destroy');

        // Partenaires
        Route::get('/partners', [AdminPartnerController::class, 'index'])
            ->name('partners.index');
        Route::post('/partners', [AdminPartnerController::class, 'store'])
            ->name('partners.store');
        Route::post('/partners/{partner}', [AdminPartnerController::class, 'update'])
            ->name('partners.update');
        Route::delete('/partners/{partner}', [AdminPartnerController::class, 'destroy'])
            ->name('partners.destroy');

        // Notifications
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');

        // Support (Helpdesk Admin)
        Route::get('/support', [App\Http\Controllers\Admin\SupportController::class, 'index'])
            ->name('support.index');

        Route::get('/support/{ticket}', [App\Http\Controllers\Admin\SupportController::class, 'show'])
            ->name('support.show');

        Route::post('/support/{ticket}/reply', [App\Http\Controllers\Admin\SupportController::class, 'reply'])
            ->name('support.reply');

        Route::patch('/support/{ticket}/resolve', [App\Http\Controllers\Admin\SupportController::class, 'resolve'])
            ->name('support.resolve');

        // ── Messages suspects (Robot Moderateur) ──
        Route::get('/flagged-messages', [AdminFlaggedMessageController::class, 'index'])
            ->name('flagged-messages.index');

        // ── Backup base de donnees ──
        Route::get('/backup/download', [App\Http\Controllers\Admin\DatabaseBackupController::class, 'download'])
            ->middleware('admin.permission:super_admin')
            ->name('backup.download');

        // ── Santé système ──
        Route::get('/health', [App\Http\Controllers\Admin\SystemHealthController::class, 'index'])
            ->name('health.index');
        Route::post('/health/toggle-alerts', [App\Http\Controllers\Admin\SystemHealthController::class, 'toggleAlerts'])
            ->name('health.toggle-alerts');
        Route::post('/health/run-check', [App\Http\Controllers\Admin\SystemHealthController::class, 'runAutoCheck'])
            ->name('health.run-check');
        Route::post('/health/clear-log', [App\Http\Controllers\Admin\SystemHealthController::class, 'clearLog'])
            ->name('health.clear-log');

        // ── Documents KYC securises (acces admin uniquement) ──
        Route::get('/kyc/document/{path}', [App\Http\Controllers\Admin\SecureFileController::class, 'kycDocument'])
            ->where('path', '.*')
            ->name('kyc.document');

        // ── Top 100 classements ──
        Route::get('/rankings/creators', [App\Http\Controllers\Admin\TopRankingController::class, 'creators'])
            ->name('rankings.creators');
        Route::get('/rankings/vendors', [App\Http\Controllers\Admin\TopRankingController::class, 'vendors'])
            ->name('rankings.vendors');

        // ── Panneau de Securite (Intrusions & Monitoring) ──
        Route::get('/security', [App\Http\Controllers\Admin\SecurityController::class, 'index'])
            ->middleware('admin.permission:super_admin')
            ->name('security.index');
        Route::post('/security/block-ip', [App\Http\Controllers\Admin\SecurityController::class, 'blockIp'])
            ->middleware('admin.permission:super_admin')
            ->name('security.block-ip');
        Route::delete('/security/unblock-ip/{blockedIp}', [App\Http\Controllers\Admin\SecurityController::class, 'unblockIp'])
            ->middleware('admin.permission:super_admin')
            ->name('security.unblock-ip');

        // Appareils de confiance admin
        Route::post('/security/trusted-device', [App\Http\Controllers\Admin\SecurityController::class, 'addTrustedDevice'])
            ->middleware('admin.permission:super_admin')
            ->name('security.trusted-device.add');
        Route::delete('/security/trusted-device/{device}', [App\Http\Controllers\Admin\SecurityController::class, 'removeTrustedDevice'])
            ->middleware('admin.permission:super_admin')
            ->name('security.trusted-device.remove');

        // ── Visitor Analytics ──
        Route::get('/visitors', [App\Http\Controllers\Admin\VisitorAnalyticsController::class, 'index'])
            ->name('visitors.index');
    });

// Retour d'impersonation — accessible par vendor/influencer en session ghost
Route::post('/admin/impersonate/stop', [AdminImpersonationController::class, 'stop'])
    ->middleware(['auth:vendor,influencer'])
    ->name('admin.impersonate.stop');

// ── Time tracking beacon (public, CSRF-exempt) ──
Route::post('/track/time', [App\Http\Controllers\Admin\VisitorAnalyticsController::class, 'trackTime'])
    ->name('track.time');

require __DIR__.'/auth.php';
