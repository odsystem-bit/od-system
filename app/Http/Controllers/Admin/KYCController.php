<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignTier;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\KycLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class KYCController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $tab = $request->input('tab', 'kyc');

        // --- KYC pending ---
        $pendingKyc = User::where('kyc_status', 'pending')
            ->select(
                'id', 'name', 'email', 'role', 'country', 'kyc_status',
                'kyc_document_front', 'kyc_document_back', 'kyc_document_selfie',
                'tiktok_followers', 'instagram_followers', 'facebook_followers',
                'youtube_followers', 'snapchat_followers', 'tier',
                'created_at'
            )
            ->latest()
            ->paginate(25, ['*'], 'kyc_page');

        // Convert raw paths to accessible URLs + compute tier recommendation
        $pendingKyc->getCollection()->transform(function ($user) {
            $user->kyc_front_url  = $user->kyc_document_front
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_front)])
                : null;
            $user->kyc_back_url   = $user->kyc_document_back
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_back)])
                : null;
            $user->kyc_selfie_url = $user->kyc_document_selfie
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_selfie)])
                : null;

            // Robot : palier recommande selon le total d'abonnes
            $user->total_followers = $user->total_followers;
            $user->recommended_tier = CampaignTier::fromFollowers($user->total_followers)->value;

            return $user;
        });

        // Load KYC history for each pending user
        $kycHistory = KycLog::whereIn('user_id', $pendingKyc->pluck('id'))
            ->with('admin:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');

        // --- VIP requests (créateurs de contenu ayant explicitement fait une demande VIP) ---
        $pendingVip = User::where('role', UserRole::INFLUENCER)
            ->where('is_vip', false)
            ->whereNotNull('vip_requested_at')
            ->select(
                'id', 'name', 'email', 'country',
                'tiktok_url', 'tiktok_followers',
                'instagram_url', 'instagram_followers',
                'facebook_url', 'facebook_followers',
                'youtube_url', 'youtube_followers',
                'snapchat_url', 'snapchat_followers',
                'is_vip', 'vip_requested_at', 'created_at'
            )
            ->orderBy('vip_requested_at')
            ->paginate(25, ['*'], 'vip_page');

        return Inertia::render('KYC/Index', [
            'tab'        => $tab,
            'pendingKyc' => $pendingKyc,
            'kycHistory' => $kycHistory,
            'pendingVip' => $pendingVip,
        ]);
    }
}
