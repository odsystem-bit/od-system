<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Enums\CampaignTier;
use App\Enums\Niche;
use App\Enums\ParticipationMode;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\SmartLink;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * SystemCampaignController — Campagnes Officielles MANTOTA (God Mode).
 *
 * Permet a l'admin de creer et gerer des campagnes systeme dont le budget
 * est virtuel : aucun wallet n'est debite.
 * Le flag is_system_campaign = true signale une campagne officielle.
 */
class SystemCampaignController extends Controller
{
    /** Plateformes autorisees */
    private const ALLOWED_PLATFORMS = ['tiktok', 'facebook', 'instagram', 'youtube', 'snapchat'];

    /** Prix minimum par clic en FCFA */
    private const MIN_CLICK_PRICE = 25;

    /** Budget minimum de campagne en FCFA */
    private const MIN_BUDGET = 1000;

    /** Pays cibles autorises (codes ISO) */
    private const TARGET_COUNTRIES = ['BJ', 'CI', 'SN', 'TG', 'CM'];

    /** Correspondance code → nom pour affichage */
    private const COUNTRY_NAMES = [
        'BJ' => 'Bénin',
        'CI' => "Côte d'Ivoire",
        'SN' => 'Sénégal',
        'TG' => 'Togo',
        'CM' => 'Cameroun',
    ];

    /** MIME autorises */
    private const ALLOWED_MIMES = 'image/jpeg,image/png,video/mp4,video/quicktime';

    /** Taille max du fichier : 50 Mo */
    private const MAX_FILE_SIZE_KB = 51200;

    // ──────────────────────────────────────────────
    //  INDEX — Liste des campagnes systeme
    // ──────────────────────────────────────────────

    public function index(): InertiaResponse
    {
        $campaigns = Campaign::query()
            ->where('is_system_campaign', true)
            ->with('smartLinks:id,campaign_id')
            ->withCount(['smartLinks as total_clicks_count' => function (Builder $q) {
                $q->withCount('clickLogs');
            }])
            ->withCount(['smartLinks'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Campaign $c) {
                // Compter les clics totaux et valides via click_logs
                $smartLinkIds = $c->smartLinks->pluck('id')->toArray();
                
                $totalClicks = \Illuminate\Support\Facades\DB::table('click_logs')
                    ->whereIn('smart_link_id', $smartLinkIds)
                    ->count();

                $validClicks = \Illuminate\Support\Facades\DB::table('click_logs')
                    ->whereIn('smart_link_id', $smartLinkIds)
                    ->where('is_paid', true)
                    ->count();

                return [
                    'id'               => $c->id,
                    'title'            => $c->title,
                    'target_url'       => $c->target_url,
                    'target_country'   => $c->target_country,
                    'click_price'      => (float) $c->click_price,
                    'total_budget'     => (float) $c->total_budget,
                    'remaining_budget' => (float) $c->remaining_budget,
                    'niche'            => $c->niche,
                    'platforms'        => $c->platforms ?? [],
                    'status'           => $c->status->value,
                    'total_clicks'     => $totalClicks,
                    'valid_clicks'     => $validClicks,
                    'smart_links_count' => $c->smart_links_count,
                    'created_at'       => $c->created_at->toDateString(),
                ];
            });

        // KPI summary
        $totalBudget    = $campaigns->sum('total_budget');
        $totalRemaining = $campaigns->sum('remaining_budget');
        $totalSpent     = $totalBudget - $totalRemaining;
        $totalValidClicks = $campaigns->sum('valid_clicks');
        $activeCount    = $campaigns->where('status', 'active')->count();

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns->values(),
            'summary'   => [
                'total_campaigns'  => $campaigns->count(),
                'active_campaigns' => $activeCount,
                'total_budget'     => (float) $totalBudget,
                'total_spent'      => (float) $totalSpent,
                'total_remaining'  => (float) $totalRemaining,
                'total_valid_clicks' => $totalValidClicks,
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    //  CREATE — Formulaire de creation
    // ──────────────────────────────────────────────

    public function create(): InertiaResponse
    {
        return Inertia::render('Campaigns/Create', [
            'available_niches' => Niche::options(),
            'tiers'            => CampaignTier::allTiersData(),
            'tier_thresholds'  => [
                'argent' => CampaignTier::argentThreshold(),
                'or'     => CampaignTier::orThreshold(),
            ],
            'countries' => self::TARGET_COUNTRIES,
            'country_names' => self::COUNTRY_NAMES,
            'restricted_circle_multiplier' => CampaignTier::restrictedCircleMultiplier(),
            'participation_modes'          => ParticipationMode::options(),
            'cost_per_participation'       => CampaignTier::costPerParticipation(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  STORE — Creation de campagne systeme
    // ──────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'target_url'     => ['required', 'url', 'max:2048'],
            'media'          => ['required', 'file', 'mimetypes:' . self::ALLOWED_MIMES, 'max:' . self::MAX_FILE_SIZE_KB],
            'target_country'   => ['required', 'array', 'min:1'],
            'target_country.*' => [Rule::in(self::TARGET_COUNTRIES)],
            'niche'          => ['required', 'string', Rule::in(Niche::values())],
            'click_price'    => ['required', 'numeric', 'min:' . self::MIN_CLICK_PRICE],
            'platforms'      => ['required', 'array', 'min:1'],
            'platforms.*'    => ['string', Rule::in(self::ALLOWED_PLATFORMS)],
            'total_budget'   => ['required', 'numeric', 'min:' . self::MIN_BUDGET],
            'open_sea'            => ['sometimes', 'boolean'],
            'restricted_circle'   => ['sometimes', 'boolean'],
            'participation_mode'  => ['sometimes', 'string', Rule::in(ParticipationMode::values())],
            'instructions'        => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required'          => 'Le titre de la campagne est obligatoire.',
            'target_url.required'     => 'Le lien cible est obligatoire pour une campagne systeme.',
            'target_url.url'          => 'Le lien cible doit etre une URL valide.',
            'media.required'          => 'Veuillez uploader une image ou une video promotionnelle.',
            'media.mimetypes'         => 'Format accepte : JPG, PNG (images) ou MP4, MOV (videos).',
            'media.max'               => 'Le fichier ne doit pas depasser 50 Mo.',
            'target_country.required' => 'Selectionnez au moins un pays cible.',
            'target_country.min'      => 'Selectionnez au moins un pays cible.',
            'niche.required'          => 'Veuillez selectionner une niche.',
            'click_price.required'    => 'Le prix par clic (CPC) est obligatoire.',
            'click_price.min'         => 'Le prix par clic minimum est de ' . self::MIN_CLICK_PRICE . ' FCFA.',
            'platforms.required'      => 'Selectionnez au moins un reseau social.',
            'total_budget.required'   => 'Le budget total est obligatoire.',
            'total_budget.min'        => 'Le budget minimum est de ' . self::MIN_BUDGET . ' FCFA.',
        ]);

        // Upload media
        $uploadedFile = $request->file('media');
        $mimeType     = $uploadedFile->getMimeType();
        $mediaType    = str_starts_with($mimeType, 'video/') ? 'video' : 'image';
        $mediaPath    = $uploadedFile->store('campaigns/media', 'public');

        // Tier automatique
        $tier              = CampaignTier::fromBudget((float) $validated['total_budget']);
        $openSea           = (bool) ($validated['open_sea'] ?? false);
        $restrictedCircle  = (bool) ($validated['restricted_circle'] ?? false);
        $participationMode = $validated['participation_mode'] ?? 'open';

        // Calcul max_participants si mode != open
        $maxParticipants = null;
        if ($participationMode !== 'open') {
            $costPerParticipation = CampaignTier::costPerParticipation()[$tier->value] ?? 2000;
            $maxParticipants = max(1, (int) floor((float) $validated['total_budget'] / $costPerParticipation));
        }

        Campaign::create([
            'vendor_id'          => auth('admin')->id(),
            'title'              => $validated['title'],
            'target_url'         => $validated['target_url'],
            'media_path'         => $mediaPath,
            'media_type'         => $mediaType,
            'target_country'     => $validated['target_country'],
            'click_price'        => $validated['click_price'],
            'total_budget'       => $validated['total_budget'],
            'remaining_budget'   => $validated['total_budget'],
            'platforms'          => $validated['platforms'],
            'niche'              => $validated['niche'],
            'instructions'       => $validated['instructions'] ?? null,
            'status'             => CampaignStatus::ACTIVE,
            'tier'               => $tier,
            'open_sea'           => $openSea,
            'restricted_circle'  => $restrictedCircle,
            'participation_mode' => $participationMode,
            'max_participants'   => $maxParticipants,
            'is_system_campaign' => true,
        ]);

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campagne Systeme creee avec succes.');
    }

    // ──────────────────────────────────────────────
    //  EDIT — Formulaire d'edition
    // ──────────────────────────────────────────────

    public function edit(Campaign $campaign): InertiaResponse
    {
        abort_unless((bool) $campaign->is_system_campaign, 403);

        return Inertia::render('Campaigns/Edit', [
            'campaign'         => [
                'id'               => $campaign->id,
                'title'            => $campaign->title,
                'target_url'       => $campaign->target_url,
                'target_country'   => $campaign->target_country,
                'click_price'      => (float) $campaign->click_price,
                'total_budget'     => (float) $campaign->total_budget,
                'remaining_budget' => (float) $campaign->remaining_budget,
                'niche'            => $campaign->niche,
                'platforms'        => $campaign->platforms ?? [],
                'status'           => $campaign->status->value,
            ],
            'available_niches' => Niche::options(),
            'countries'        => self::TARGET_COUNTRIES,
            'country_names'    => self::COUNTRY_NAMES,
        ]);
    }

    // ──────────────────────────────────────────────
    //  UPDATE — Mise a jour campagne systeme
    // ──────────────────────────────────────────────

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless((bool) $campaign->is_system_campaign, 403);

        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'target_url'     => ['required', 'url', 'max:2048'],
            'click_price'    => ['required', 'numeric', 'min:' . self::MIN_CLICK_PRICE],
            'budget_change'  => ['nullable', 'numeric'],
        ], [
            'title.required'       => 'Le titre est obligatoire.',
            'target_url.required'  => 'Le lien cible est obligatoire.',
            'target_url.url'       => 'Le lien cible doit etre une URL valide.',
            'click_price.required' => 'Le CPC est obligatoire.',
            'click_price.min'      => 'Le CPC minimum est de ' . self::MIN_CLICK_PRICE . ' FCFA.',
        ]);

        $campaign->title     = $validated['title'];
        $campaign->target_url = $validated['target_url'];
        $campaign->click_price = $validated['click_price'];

        // Ajustement du budget virtuel
        $budgetChange = (float) ($validated['budget_change'] ?? 0);
        if ($budgetChange !== 0.0) {
            $campaign->total_budget     = max(0, (float) $campaign->total_budget + $budgetChange);
            $campaign->remaining_budget = max(0, (float) $campaign->remaining_budget + $budgetChange);

            // Reactiver si on ajoute du budget et la campagne etait epuisee
            if ($budgetChange > 0 && $campaign->status === CampaignStatus::COMPLETED) {
                $campaign->status = CampaignStatus::ACTIVE;
            }
        }

        $campaign->save();

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campagne mise a jour.');
    }

    // ──────────────────────────────────────────────
    //  SHOW — Detail campagne (toutes campagnes)
    // ──────────────────────────────────────────────

    public function show(Campaign $campaign): InertiaResponse
    {
        $campaign->load('vendor:id,name,email,role,shop_name,business_name,profile_photo');
        $campaign->loadCount('smartLinks');

        $smartLinks = $campaign->smartLinks()
            ->with('influencer:id,name,slug,tier,profile_photo,tiktok_followers,instagram_followers,facebook_followers,youtube_followers,snapchat_followers')
            ->withCount([
                'clickLogs as total_clicks_count',
                'clickLogs as paid_clicks_count' => fn ($q) => $q->where('is_paid', true),
            ])
            ->get();

        $smartLinkIds = $smartLinks->pluck('id');

        $paidClicks  = DB::table('click_logs')->whereIn('smart_link_id', $smartLinkIds)->where('is_paid', true)->count();
        $totalClicks = DB::table('click_logs')->whereIn('smart_link_id', $smartLinkIds)->count();

        // Invalid reasons breakdown
        $invalidReasons = DB::table('click_logs')
            ->selectRaw('invalid_reason, COUNT(*) as total')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->where('is_valid', false)
            ->whereNotNull('invalid_reason')
            ->groupBy('invalid_reason')
            ->get();

        // Top countries
        $topCountries = DB::table('click_logs')
            ->selectRaw('clicker_country, COUNT(*) as total')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->whereNotNull('clicker_country')
            ->groupBy('clicker_country')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Anti-fraud stats (new fingerprint pipeline)
        $vpnBlocked = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->where('is_vpn', true)
            ->count();

        $deviceDuplicates = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->where('invalid_reason', 'duplicate_device')
            ->count();

        $botsBlocked = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->whereIn('invalid_reason', ['bot_detected', 'suspicious_timing'])
            ->count();

        $uniqueDevices = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->whereNotNull('device_id')
            ->distinct('device_id')
            ->count('device_id');

        // Recent fraud clicks (last 50)
        $recentFraudClicks = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->where('is_valid', false)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['ip_address', 'device_id', 'user_agent_hash', 'clicker_country', 'is_vpn', 'invalid_reason', 'created_at']);

        // Order stats per influencer
        $influencerIds = $smartLinks->pluck('influencer_id')->unique()->filter();
        $orderStats = [];
        if ($influencerIds->isNotEmpty()) {
            $orderStats = Order::where('campaign_id', $campaign->id)
                ->whereIn('influencer_id', $influencerIds)
                ->selectRaw('influencer_id, COUNT(*) as orders_count, COALESCE(SUM(commission_amount), 0) as total_commission')
                ->groupBy('influencer_id')
                ->get()
                ->keyBy('influencer_id')
                ->toArray();
        }

        // Per-influencer stats
        $influencerStats = $smartLinks->map(function ($link) use ($campaign, $orderStats) {
            $inf   = $link->influencer;
            $infId = $link->influencer_id;
            $paid  = (int) $link->paid_clicks_count;
            $cpc   = $paid * (float) $campaign->effective_click_price;
            $orders    = isset($orderStats[$infId]) ? (int) $orderStats[$infId]['orders_count'] : 0;
            $cpaEarn   = isset($orderStats[$infId]) ? (float) $orderStats[$infId]['total_commission'] : 0;

            return [
                'influencer_id'    => $infId,
                'influencer_name'  => $inf->name ?? 'Inconnu',
                'influencer_slug'  => $inf->slug ?? null,
                'influencer_photo' => $inf->profile_photo ?? null,
                'influencer_tier'  => $inf->tier ?? 'bronze',
                'total_clicks'     => (int) $link->total_clicks_count,
                'paid_clicks'      => $paid,
                'orders_count'     => $orders,
                'cpc_earnings'     => $cpc,
                'cpa_earnings'     => $cpaEarn,
                'total_paid'       => $cpc + $cpaEarn,
                'social_followers' => [
                    'tiktok'    => (int) ($inf->tiktok_followers ?? 0),
                    'instagram' => (int) ($inf->instagram_followers ?? 0),
                    'facebook'  => (int) ($inf->facebook_followers ?? 0),
                    'youtube'   => (int) ($inf->youtube_followers ?? 0),
                    'snapchat'  => (int) ($inf->snapchat_followers ?? 0),
                ],
            ];
        })->sortByDesc('total_paid')->values();

        return Inertia::render('Campaigns/Show', [
            'campaign'          => $campaign,
            'paidClicks'        => $paidClicks,
            'totalClicks'       => $totalClicks,
            'influencerStats'   => $influencerStats,
            'invalidReasons'    => $invalidReasons,
            'topCountries'      => $topCountries,
            'vpnBlocked'        => $vpnBlocked,
            'deviceDuplicates'  => $deviceDuplicates,
            'botsBlocked'       => $botsBlocked,
            'uniqueDevices'     => $uniqueDevices,
            'recentFraudClicks' => $recentFraudClicks,
        ]);
    }

    // ──────────────────────────────────────────────
    //  TOGGLE PAUSE — Active / Pause
    // ──────────────────────────────────────────────

    public function togglePause(Campaign $campaign): RedirectResponse
    {
        abort_unless((bool) $campaign->is_system_campaign, 403);

        if ($campaign->status === CampaignStatus::ACTIVE) {
            $campaign->update(['status' => CampaignStatus::PAUSED]);
        } elseif ($campaign->status === CampaignStatus::PAUSED) {
            $campaign->update(['status' => CampaignStatus::ACTIVE]);
        }

        return redirect()->back()->with('success', 'Statut mis a jour.');
    }

    // ──────────────────────────────────────────────
    //  DESTROY — Suppression
    // ──────────────────────────────────────────────

    public function destroy(Campaign $campaign): RedirectResponse
    {
        // Rembourser le budget restant au vendeur avant suppression
        if ((float) $campaign->remaining_budget > 0 && $campaign->vendor_id) {
            $this->refundBeforeDelete($campaign);
        }

        // Supprimer le media associe
        if ($campaign->media_path) {
            Storage::disk('public')->delete($campaign->media_path);
        }

        // Supprimer les enregistrements lies (FK restrict)
        $campaign->smartLinks()->each(fn ($link) => $link->delete());
        $campaign->orders()->update(['campaign_id' => null]);

        $campaign->delete();

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campagne supprimee.');
    }

    /**
     * Rembourse le budget restant au vendeur avant suppression de la campagne.
     */
    private function refundBeforeDelete(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $locked = Campaign::where('id', $campaign->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $locked->remaining_budget;
            if ($amount <= 0) {
                return;
            }

            $locked->remaining_budget = 0;
            $locked->save();

            $wallet = Wallet::where('user_id', $locked->vendor_id)->lockForUpdate()->firstOrFail();
            $wallet->balance = round((float) $wallet->balance + $amount, 2);
            $wallet->save();

            Transaction::create([
                'user_id'        => $locked->vendor_id,
                'type'           => 'refund',
                'amount_target'  => round($amount, 2),
                'gateway_fee'    => 0.00,
                'mantota_markup' => 0.00,
                'amount_total'   => round($amount, 2),
                'status'         => 'completed',
                'reference'      => 'CAMP-DELETE-REFUND-' . $locked->id . '-' . now()->timestamp,
                'description'    => 'Remboursement budget restant — Campagne #' . $locked->id . ' supprimee par admin',
            ]);
        });
    }
}
