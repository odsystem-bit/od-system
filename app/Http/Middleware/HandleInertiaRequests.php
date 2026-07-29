<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Announcement;
use App\Models\Setting;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Selectionne le template Blade selon le prefixe de route.
     * Routes admin       → admin.blade.php
     * Routes vendor      → vendor.blade.php
     * Routes influencer  → influencer.blade.php
     * Toutes les autres  → app.blade.php
     */
    public function rootView(Request $request): string
    {
        if ($request->is('admin/*') || $request->is('admin')) {
            return 'admin';
        }

        if ($request->is('vendor/*') || $request->is('vendor')) {
            return 'vendor';
        }

        if ($request->is('influencer/*') || $request->is('influencer')) {
            return 'influencer';
        }

        return parent::rootView($request);
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $this->resolveAuthUser($request);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge(
                    $user->toArray(),
                    ['unread_notifications_count' => fn () => Cache::remember(
                        "unread_notif_count_{$user->id}",
                        30,
                        fn () => $user->unreadNotifications()->count()
                    )]
                ) : null,
            ],
            'admin_impersonating' => fn () => $request->session()->has('admin_impersonating_id'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'notifications' => fn () => $user
                ? Cache::remember(
                    "unread_notif_list_{$user->id}",
                    30,
                    fn () => $user->unreadNotifications()->latest()->take(5)->get()->map(fn ($n) => [
                        'id'         => $n->id,
                        'type'       => $n->data['type'] ?? 'info',
                        'title'      => $n->data['title'] ?? '',
                        'message'    => $n->data['message'] ?? '',
                        'url'        => $n->data['url'] ?? null,
                        'color'      => $n->data['color'] ?? 'slate',
                        'created_at' => $n->created_at->diffForHumans(),
                    ])->toArray()
                )
                : [],
            'global_settings' => fn () => Cache::remember('global_settings_inertia', 3600, function () {
                $keys = [
                    'company_name', 'contact_email', 'whatsapp_phone',
                    'rccm', 'ifu', 'physical_address',
                    'social_facebook', 'social_instagram', 'social_tiktok', 'social_twitter',
                    'site_logo_light', 'site_logo_dark', 'logo_width', 'logo_height',
                ];
                $settings = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
                $defaults = [
                    'company_name' => 'MANTOTA', 'contact_email' => 'contact@mantota.com',
                    'whatsapp_phone' => '', 'rccm' => '', 'ifu' => '',
                    'physical_address' => '', 'social_facebook' => '',
                    'social_instagram' => '', 'social_tiktok' => '', 'social_twitter' => '',
                    'site_logo_light' => '/images/logo-white.png',
                    'site_logo_dark' => '/images/logo-dark.png',
                    'logo_width' => '140',
                    'logo_height' => '40',
                ];
                return array_merge($defaults, $settings);
            }),
            'seo' => fn () => $request->attributes->get('seo', [
                'title' => 'MANTOTA - Reseau publicitaire 100% Performance',
                'description' => 'Vendez plus, plus vite. Connectez vendeurs et créateurs de contenu.',
                'image' => null,
            ]),
            'announcements' => fn () => $this->getActiveAnnouncements($request),
            'welcome_popup' => fn () => $this->getWelcomePopup($request, $user),
        ];
    }

    /**
     * Resout l'utilisateur connecte selon le guard applicable.
     */
    private function resolveAuthUser(Request $request): mixed
    {
        if ($request->is('admin/*') || $request->is('admin')) {
            return Auth::guard('admin')->user();
        }

        if ($request->is('vendor/*') || $request->is('vendor')) {
            return Auth::guard('vendor')->user();
        }

        if ($request->is('influencer/*') || $request->is('influencer')) {
            return Auth::guard('influencer')->user();
        }

        return $request->user();
    }

    private function getActiveAnnouncements(Request $request): array
    {
        $role = 'all';
        if ($request->is('admin/*') || $request->is('admin')) {
            $role = 'admin';
        } elseif ($request->is('vendor/*') || $request->is('vendor')) {
            $role = 'vendor';
        } elseif ($request->is('influencer/*') || $request->is('influencer')) {
            $role = 'influencer';
        }

        return Announcement::where('is_active', true)
            ->whereIn('target_role', ['all', $role])
            ->latest()
            ->limit(3)
            ->get(['id', 'message'])
            ->toArray();
    }

    private function getWelcomePopup(Request $request, $user): ?array
    {
        if (!$user || $user->welcome_popup_seen) {
            return null;
        }

        if ($request->is('vendor/*') || $request->is('vendor')) {
            return [
                'message' => Setting::get('welcome_popup_vendor', 'Bienvenue sur MANTOTA !'),
                'dismiss_route' => 'vendor.welcome-popup.dismiss',
            ];
        }

        if ($request->is('influencer/*') || $request->is('influencer')) {
            return [
                'message' => Setting::get('welcome_popup_influencer', 'Bienvenue sur MANTOTA !'),
                'dismiss_route' => 'influencer.welcome-popup.dismiss',
            ];
        }

        return null;
    }
}
