<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InfluencerProfileController extends Controller
{
    public function show(User $user): InertiaResponse
    {
        abort_unless($user->role === UserRole::INFLUENCER, 404);

        return Inertia::render('Influencers/Show', [
            'influencer' => [
                'id'                  => $user->id,
                'name'                => $user->name,
                'slug'                => $user->slug,
                'tier'                => $user->tier ?? 'bronze',
                'country'             => $user->country,
                'niches'              => $user->niches ?? [],
                'total_followers'     => $user->total_followers,
                'tiktok_url'          => $user->tiktok_url,
                'tiktok_followers'    => (int) ($user->tiktok_followers ?? 0),
                'instagram_url'       => $user->instagram_url,
                'instagram_followers' => (int) ($user->instagram_followers ?? 0),
                'facebook_url'        => $user->facebook_url,
                'facebook_followers'  => (int) ($user->facebook_followers ?? 0),
                'youtube_url'         => $user->youtube_url,
                'youtube_followers'   => (int) ($user->youtube_followers ?? 0),
                'snapchat_url'        => $user->snapchat_url,
                'snapchat_followers'  => (int) ($user->snapchat_followers ?? 0),
                'services_count'      => $user->influencerServices()->count(),
            ],
        ]);
    }
}
