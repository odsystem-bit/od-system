<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Enums\CampaignStatus;
use App\Enums\CampaignTier;
use App\Enums\Niche;
use App\Enums\ParticipationMode;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\SmartLink;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Notifications\CampaignRejectedNotification;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * CampaignController — Moteur Ads MANTOTA (cote vendeur).
 *
 * Responsabilites :
 *  - Afficher le formulaire de creation en 4 sections.
 *  - Valider les donnees, gerer l'upload media, debiter le wallet.
 *  - Transaction atomique avec lockForUpdate() sur le wallet.
 *  - VERROU KYC : bloquer toute creation si kyc_status !== 'approved'.
 *
 * Modele de remuneration 100% Performance :
 *  - CPC (cout par clic) : minimum 25 FCFA, fixe par le vendeur.
 *  - Commission sur vente : pourcentage partenaire lie au produit.
 *  - remaining_budget decremente a chaque clic facturable.
 *
 * Reseaux autorises : TikTok, Facebook, Instagram, YouTube, Snapchat.
 * LinkedIn N'EST PAS autorise.
 */
class CampaignController extends Controller
{
    // ──────────────────────────────────────────────
    //  Constantes
    // ──────────────────────────────────────────────

    /** Plateformes autorisees — ZERO LinkedIn */
    private const ALLOWED_PLATFORMS = ['tiktok', 'facebook', 'instagram', 'youtube', 'snapchat'];

    /** Prix minimum par clic en FCFA (defaut, ecrase par settings) */
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
    //  Liste des campagnes du vendeur
    // ──────────────────────────────────────────────

    public function index(): InertiaResponse
    {
        $user   = auth()->user();
        $wallet = $user->wallet;

        $campaigns = Campaign::query()
            ->where('vendor_id', $user->id)
            ->where('status', '!=', CampaignStatus::DELETED)
            ->withCount('smartLinks')
            ->latest()
            ->paginate(15);

        return Inertia::render('Campaigns/Index', [
            'campaigns'         => $campaigns,
            'available_balance' => $wallet ? (float) $wallet->balance : 0.00,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Details d'une campagne
    // ──────────────────────────────────────────────

    public function show(Campaign $campaign): InertiaResponse
    {
        $this->authorizeVendor($campaign);

        $campaign->loadCount('smartLinks');

        // ── SmartLinks avec créateur de contenu + stats clics ──
        $smartLinks = $campaign->smartLinks()
            ->with('influencer:id,name,slug,tier,tiktok_followers,instagram_followers,facebook_followers,youtube_followers,snapchat_followers')
            ->withCount([
                'clickLogs as total_clicks_count',
                'clickLogs as paid_clicks_count' => fn ($q) => $q->where('is_paid', true),
            ])
            ->get();

        $smartLinkIds = $smartLinks->pluck('id');

        // Nombre de clics payes
        $paidClicks = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->where('is_paid', true)
            ->count();

        // Nombre total de clics (payes + non payes)
        $totalClicks = DB::table('click_logs')
            ->whereIn('smart_link_id', $smartLinkIds)
            ->count();

        // ── Stats commandes par créateur de contenu ──
        $influencerIds = $smartLinks->pluck('influencer_id')->unique()->filter();

        $orderStats = [];
        if ($influencerIds->isNotEmpty()) {
            $orderQuery = \App\Models\Order::where('vendor_id', $campaign->vendor_id)
                ->whereIn('influencer_id', $influencerIds)
                ->where('campaign_id', $campaign->id);

            $orderStats = $orderQuery
                ->selectRaw('influencer_id, COUNT(*) as orders_count, COALESCE(SUM(commission_amount), 0) as total_commission')
                ->groupBy('influencer_id')
                ->get()
                ->keyBy('influencer_id')
                ->toArray();
        }

        // ── Construction du tableau ROI par créateur de contenu ──
        $influencerStats = $smartLinks->map(function ($link) use ($campaign, $orderStats) {
            $influencer  = $link->influencer;
            $infId       = $link->influencer_id;
            $paidClicks  = (int) $link->paid_clicks_count;
            $cpcEarnings = $paidClicks * (float) $campaign->effective_click_price;
            $orders      = isset($orderStats[$infId]) ? (int) $orderStats[$infId]['orders_count'] : 0;
            $cpaEarnings = isset($orderStats[$infId]) ? (float) $orderStats[$infId]['total_commission'] : 0;

            return [
                'influencer_id'   => $infId,
                'influencer_name' => $influencer->name ?? 'Inconnu',
                'influencer_slug' => $influencer->slug ?? null,
                'influencer_tier' => $influencer->tier ?? 'bronze',
                'total_clicks'    => (int) $link->total_clicks_count,
                'paid_clicks'     => $paidClicks,
                'orders_count'    => $orders,
                'cpc_earnings'    => $cpcEarnings,
                'cpa_earnings'    => $cpaEarnings,
                'total_paid'      => $cpcEarnings + $cpaEarnings,
                'social_followers' => [
                    'tiktok'    => (int) ($influencer->tiktok_followers ?? 0),
                    'instagram' => (int) ($influencer->instagram_followers ?? 0),
                    'facebook'  => (int) ($influencer->facebook_followers ?? 0),
                    'youtube'   => (int) ($influencer->youtube_followers ?? 0),
                    'snapchat'  => (int) ($influencer->snapchat_followers ?? 0),
                ],
            ];
        })->values();

        $wallet = Wallet::where('user_id', auth()->id())->first();

        return Inertia::render('Campaigns/Show', [
            'campaign'          => $campaign,
            'paidClicks'        => $paidClicks,
            'totalClicks'       => $totalClicks,
            'influencerStats'   => $influencerStats,
            'available_balance' => $wallet ? (float) $wallet->balance : 0.00,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Pause / Reprendre
    // ──────────────────────────────────────────────

    public function togglePause(Campaign $campaign): RedirectResponse
    {
        $this->authorizeVendor($campaign);

        if ($campaign->status === CampaignStatus::ACTIVE) {
            $campaign->update([
                'status'    => CampaignStatus::PAUSED,
                'paused_at' => now(),
            ]);
            return back()->with('success', 'Campagne mise en pause (reprise automatique dans 1h max).');
        }

        if ($campaign->status === CampaignStatus::PAUSED) {
            $campaign->update([
                'status'    => CampaignStatus::ACTIVE,
                'paused_at' => null,
            ]);
            return back()->with('success', 'Campagne relancee.');
        }

        return back()->withErrors(['campaign' => 'Seules les campagnes actives ou en pause peuvent etre basculees.']);
    }

    // ──────────────────────────────────────────────
    //  Ajouter du budget
    // ──────────────────────────────────────────────

    public function addBudget(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeVendor($campaign);

        if (! in_array($campaign->status, [CampaignStatus::ACTIVE, CampaignStatus::PAUSED, CampaignStatus::COMPLETED])) {
            return back()->withErrors(['campaign' => 'Impossible d\'ajouter du budget a cette campagne.']);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:500'],
        ], [
            'amount.required' => 'Le montant est obligatoire.',
            'amount.min'      => 'Le montant minimum est de 500 FCFA.',
        ]);

        $amount = (float) $validated['amount'];
        $wallet = Wallet::where('user_id', auth()->id())->first();

        // Commission MANTOTA sur l'ajout de budget
        $campaignCommissionPercent = (float) mantota_setting('campaign_commission_percent', 5);

        // Reduction ambassadeur
        if (auth()->user()->is_ambassador) {
            $discount = (float) mantota_setting('ambassador_commission_discount', 50);
            $campaignCommissionPercent = round($campaignCommissionPercent * (1 - $discount / 100), 2);
        }

        $commission = round($amount * ($campaignCommissionPercent / 100), 2);
        $totalDebit = $amount + $commission;

        if (! $wallet || (float) $wallet->balance < $totalDebit) {
            return back()->withErrors(['amount' => 'Solde insuffisant. Vous avez besoin de ' . number_format($totalDebit, 0, ',', ' ') . ' FCFA (budget + ' . $campaignCommissionPercent . '% commission).']);
        }

        DB::transaction(function () use ($wallet, $campaign, $amount, $commission, $totalDebit): void {
            $lockedWallet = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $lockedWallet->balance < $totalDebit) {
                throw new \RuntimeException('Solde insuffisant apres verrouillage.');
            }

            $lockedWallet->balance = (float) $lockedWallet->balance - $totalDebit;
            $lockedWallet->save();

            $lockedCampaign = Campaign::where('id', $campaign->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCampaign->remaining_budget = (float) $lockedCampaign->remaining_budget + $amount;
            $lockedCampaign->total_budget     = (float) $lockedCampaign->total_budget + $amount;
            $lockedCampaign->save();

            // Si la campagne etait completed (epuisee), la relancer
            if ($lockedCampaign->status === CampaignStatus::COMPLETED) {
                $lockedCampaign->update(['status' => CampaignStatus::ACTIVE]);
            }

            // Transaction d'audit : commission MANTOTA
            if ($commission > 0) {
                Transaction::create([
                    'user_id'        => auth()->id(),
                    'type'           => 'fee',
                    'amount_target'  => round($commission, 2),
                    'gateway_fee'    => 0.00,
                    'mantota_markup' => round($commission, 2),
                    'amount_total'   => round($commission, 2),
                    'status'         => 'completed',
                    'reference'      => 'CAMP-FEE-' . $lockedCampaign->id . '-' . now()->timestamp,
                    'description'    => 'Commission MANTOTA — Ajout budget campagne #' . $lockedCampaign->id,
                ]);
            }
        });

        Cache::forget('admin.dashboard');

        return back()->with('success', 'Budget de ' . number_format($amount, 0, ',', ' ') . ' FCFA ajoute avec succes.');
    }

    // ──────────────────────────────────────────────
    //  Suppression (Soft — status = deleted)
    // ──────────────────────────────────────────────

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorizeVendor($campaign);

        $refundAmount = (float) $campaign->remaining_budget;

        DB::transaction(function () use ($campaign, $refundAmount): void {
            /** @var Campaign $locked */
            $locked = Campaign::where('id', $campaign->id)->lockForUpdate()->firstOrFail();
            $refund = (float) $locked->remaining_budget;

            $locked->update([
                'status'           => CampaignStatus::DELETED,
                'remaining_budget' => 0,
            ]);

            if ($refund > 0) {
                /** @var Wallet $wallet */
                $wallet = Wallet::where('user_id', $locked->vendor_id)->lockForUpdate()->firstOrFail();
                $wallet->balance = round((float) $wallet->balance + $refund, 2);
                $wallet->save();

                Transaction::create([
                    'user_id'        => $locked->vendor_id,
                    'type'           => 'refund',
                    'amount_target'  => round($refund, 2),
                    'gateway_fee'    => 0.00,
                    'mantota_markup' => 0.00,
                    'amount_total'   => round($refund, 2),
                    'status'         => 'completed',
                    'reference'      => 'CAMP-REFUND-' . $locked->id . '-' . now()->timestamp,
                    'description'    => 'Remboursement budget restant — Campagne #' . $locked->id . ' supprimee',
                ]);
            }
        });

        Cache::forget('admin.dashboard');

        return redirect()
            ->route('vendor.campaigns.index')
            ->with('success', 'Campagne supprimee. ' . ($refundAmount > 0 ? number_format($refundAmount, 0, ',', ' ') . ' FCFA rembourses dans votre portefeuille.' : ''));
    }

    // ──────────────────────────────────────────────
    //  Formulaire de creation
    // ──────────────────────────────────────────────

    /**
     * Affiche le formulaire de creation avec le solde, le KYC
     * et la liste des produits du vendeur (pour le partenariat).
     */
    public function create(): InertiaResponse
    {
        $user   = auth()->user();
        $wallet = $user->wallet;

        $products = Product::query()
            ->where('vendor_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'type', 'commission_percent']);

        return Inertia::render('Campaigns/Create', [
            'available_balance' => $wallet ? (float) $wallet->balance : 0.00,
            'kyc_status'        => $user->kyc_status ?? 'not_submitted',
            'products'          => $products,
            'available_niches'  => Niche::options(),
            'tiers'             => CampaignTier::allTiersData(),
            'tier_thresholds'   => [
                'argent' => CampaignTier::argentThreshold(),
                'or'     => CampaignTier::orThreshold(),
            ],
            'is_admin'  => $user->role === UserRole::ADMIN,
            'min_cpc'    => (int) mantota_setting('min_cpc_price', self::MIN_CLICK_PRICE),
            'countries'  => self::TARGET_COUNTRIES,
            'country_names' => self::COUNTRY_NAMES,
            'restricted_circle_multiplier' => CampaignTier::restrictedCircleMultiplier(),
            'participation_modes'          => ParticipationMode::options(),
            'cost_per_participation'       => CampaignTier::costPerParticipation(),
            'campaign_commission_percent'   => $this->effectiveCommissionPercent($user),
        ]);
    }

    // ──────────────────────────────────────────────
    //  Creation de campagne avec debit atomique
    // ──────────────────────────────────────────────

    /**
     * Valide les donnees, upload le media, verifie le solde puis
     * cree la campagne au sein d'une DB::transaction() avec lockForUpdate().
     *
     * Le total_budget est integralement deduit du wallet du vendeur
     * et transfere au remaining_budget de la campagne.
     */
    public function store(Request $request): RedirectResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->role === UserRole::ADMIN;

        // ── Verrou KYC (saute pour Admin) ──
        if (! $isAdmin && $user->kyc_status !== 'approved') {
            return back()->withErrors([
                'kyc' => 'Votre compte doit etre verifie par l\'administration avant de lancer une campagne.',
            ]);
        }

        $minCpc = (int) mantota_setting('min_cpc_price', self::MIN_CLICK_PRICE);

        // ── Validation ──
        $validated = $request->validate([
            // Section 1 — Informations de base
            'title'          => ['required', 'string', 'max:255'],
            'destination_type' => ['required', 'string', Rule::in(['product', 'shop'])],
            'product_id'     => ['nullable', 'required_if:destination_type,product', 'integer', 'exists:products,id'],
            'media'          => ['required', 'file', 'mimetypes:' . self::ALLOWED_MIMES, 'max:' . self::MAX_FILE_SIZE_KB],
            'target_country'   => ['required', 'array', 'min:1'],
            'target_country.*' => [Rule::in(self::TARGET_COUNTRIES)],

            // Niche ciblee
            'niche' => ['required', 'string', Rule::in(Niche::values())],

            // Consignes pour les créateurs de contenu
            'instructions' => ['nullable', 'string', 'max:2000'],

            // Section 2 — Remuneration CPC
            'click_price' => ['required', 'numeric', 'min:' . $minCpc],

            // Section 3 — Reseaux cibles
            'platforms'   => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', Rule::in(self::ALLOWED_PLATFORMS)],

            // Section 4 — Budget
            'total_budget'  => ['required', 'numeric', 'min:' . self::MIN_BUDGET],

            // Option Open-Sea
            'open_sea' => ['sometimes', 'boolean'],

            // Cercle Restreint (Ambassadeurs uniquement)
            'restricted_circle' => ['sometimes', 'boolean'],

            // Mode de participation
            'participation_mode' => ['sometimes', 'string', Rule::in(ParticipationMode::values())],

            // Admin uniquement — Campagne Systeme
            'is_system_campaign' => ['sometimes', 'boolean'],
        ], [
            'title.required'         => 'Le titre de la campagne est obligatoire.',
            'destination_type.required' => 'Veuillez choisir un type de destination.',
            'destination_type.in'    => 'Type de destination invalide.',
            'product_id.required_if' => 'Veuillez selectionner un produit.',
            'media.required'         => 'Veuillez uploader une image ou une video promotionnelle.',
            'media.mimetypes'        => 'Format accepte : JPG, PNG (images) ou MP4, MOV (videos).',
            'media.max'              => 'Le fichier ne doit pas depasser 50 Mo.',
            'target_country.required' => 'Veuillez selectionner au moins un pays cible.',
            'target_country.min'     => 'Veuillez selectionner au moins un pays cible.',
            'niche.required'         => 'Veuillez selectionner une niche pour cibler les createurs de contenu.',
            'niche.in'               => 'La niche selectionnee est invalide.',
            'click_price.required'   => 'Le prix par clic (CPC) est obligatoire.',
            'click_price.min'        => 'Le prix par clic minimum est de ' . $minCpc . ' FCFA.',
            'platforms.required'     => 'Selectionnez au moins un reseau social.',
            'platforms.min'          => 'Selectionnez au moins un reseau social.',
            'total_budget.required'  => 'Le budget total est obligatoire.',
            'total_budget.min'       => 'Le budget minimum est de ' . self::MIN_BUDGET . ' FCFA.',
        ]);

        // ── Flag Campagne Systeme (Admin uniquement) ──
        $isSystemCampaign = $isAdmin && ! empty($validated['is_system_campaign']);

        // ── Resolution de la destination ──
        $destinationType   = $validated['destination_type'];
        $productId         = null;
        $commissionPercent = null;
        $targetUrl         = '';

        if ($destinationType === 'product') {
            $productId = (int) $validated['product_id'];
            $product   = Product::where('id', $productId)
                ->where('vendor_id', $user->id)
                ->first();

            if (! $product) {
                return back()->withErrors([
                    'product_id' => 'Ce produit ne vous appartient pas.',
                ]);
            }

            $commissionPercent = $product->commission_percent;
            $targetUrl         = $user->slug
                ? route('shop.show', $user->slug) . '?product=' . $productId
                : route('shop.show', ['vendorSlug' => $user->slug ?? $user->id]);
        } elseif ($destinationType === 'shop') {
            $targetUrl = $user->slug
                ? route('shop.show', $user->slug)
                : route('home');
        }

        $totalDebit = (float) $validated['total_budget'];

        // ── Commission MANTOTA sur le lancement de campagne ──
        $campaignCommissionPercent = (float) mantota_setting('campaign_commission_percent', 5);

        // Reduction ambassadeur
        if ($user->is_ambassador) {
            $discount = (float) mantota_setting('ambassador_commission_discount', 50);
            $campaignCommissionPercent = round($campaignCommissionPercent * (1 - $discount / 100), 2);
        }

        $campaignCommission = round($totalDebit * ($campaignCommissionPercent / 100), 2);
        $totalDebitWithCommission = $totalDebit + $campaignCommission;

        // ── Pre-check solde (saute pour Campagne Systeme) ──
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $isSystemCampaign) {
            if (! $wallet || (float) $wallet->balance < $totalDebitWithCommission) {
                return back()->withErrors([
                    'total_budget' => 'Solde insuffisant. Vous avez besoin de ' . number_format($totalDebitWithCommission, 0, ',', ' ') . ' FCFA (budget + ' . $campaignCommissionPercent . '% commission).',
                ]);
            }
        }

        // ── Upload media ──
        $uploadedFile = $request->file('media');
        $mimeType     = $uploadedFile->getMimeType();
        $mediaType    = str_starts_with($mimeType, 'video/') ? 'video' : 'image';
        $mediaPath    = $uploadedFile->store('campaigns/media', 'public');

        // ── Calcul automatique du palier ──
        $tier             = CampaignTier::fromBudget((float) $validated['total_budget']);
        $openSea          = (bool) ($validated['open_sea'] ?? false);
        $restrictedCircle = (bool) ($validated['restricted_circle'] ?? false);

        // ── Robot Douanier : scan mots interdits ──
        $bannedKeywords = mantota_setting('banned_keywords', []);
        if (is_string($bannedKeywords)) {
            $bannedKeywords = json_decode($bannedKeywords, true) ?: [];
        }

        $rejectionReason = null;
        if (! empty($bannedKeywords)) {
            $textToScan = mb_strtolower($validated['title']);
            if ($productId) {
                $productName = Product::where('id', $productId)->value('name') ?? '';
                $textToScan .= ' ' . mb_strtolower($productName);
            }
            if (! empty($validated['instructions'])) {
                $textToScan .= ' ' . mb_strtolower($validated['instructions']);
            }

            foreach ($bannedKeywords as $keyword) {
                $keyword = trim($keyword);
                if ($keyword !== '' && mb_strpos($textToScan, mb_strtolower($keyword)) !== false) {
                    $rejectionReason = 'Mot interdit detecte : ' . $keyword;
                    break;
                }
            }
        }

        // ── Donnees communes de la campagne ──
        $campaignData = [
            'vendor_id'          => $user->id,
            'product_id'         => $productId,
            'title'              => $validated['title'],
            'target_url'         => $targetUrl,
            'media_path'         => $mediaPath,
            'media_type'         => $mediaType,
            'target_country'     => $validated['target_country'],
            'click_price'        => $validated['click_price'],
            'total_budget'       => $validated['total_budget'],
            'remaining_budget'   => $validated['total_budget'],
            'commission_percent' => $commissionPercent,
            'platforms'          => $validated['platforms'],
            'niche'              => $validated['niche'],
            'instructions'       => $validated['instructions'] ?? null,
            'status'             => $rejectionReason ? CampaignStatus::REJECTED : CampaignStatus::ACTIVE,
            'rejection_reason'   => $rejectionReason,
            'tier'               => $tier,
            'open_sea'           => $openSea,
            'restricted_circle'  => $restrictedCircle,
            'is_system_campaign' => $isSystemCampaign,
            'participation_mode'  => $validated['participation_mode'] ?? ParticipationMode::OPEN->value,
            'max_participants'    => $this->calculateMaxParticipants(
                (float) $validated['total_budget'],
                $validated['participation_mode'] ?? ParticipationMode::OPEN->value,
                $tier,
            ),
            'current_participants' => 0,
        ];

        // ── Campagne Systeme (Admin) : pas de debit wallet ──
        if ($isSystemCampaign) {
            Campaign::create($campaignData);

            return redirect()
                ->route('vendor.dashboard')
                ->with('success', 'Campagne systeme creee avec succes (aucun debit wallet).');
        }

        // ── Campagne rejetee par le Robot Douanier : pas de debit wallet ──
        if ($rejectionReason) {
            $rejectedCampaign = Campaign::create($campaignData);
            Storage::disk('public')->delete($mediaPath);

            $user->notify(new CampaignRejectedNotification($rejectedCampaign, $rejectionReason));

            return redirect()
                ->route('vendor.campaigns.index')
                ->with('error', 'Campagne rejetee automatiquement : ' . $rejectionReason);
        }

        // ── Transaction atomique : debit wallet + creation campagne ──
        try {
            DB::transaction(function () use ($wallet, $campaignData, $totalDebitWithCommission, $campaignCommission, $user): void {
                /** @var Wallet $lockedWallet */
                $lockedWallet = Wallet::where('id', $wallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $lockedWallet->balance < $totalDebitWithCommission) {
                    throw new \RuntimeException('Solde insuffisant apres verrouillage.');
                }

                // Debit du wallet vendeur (budget + commission)
                $lockedWallet->balance = (float) $lockedWallet->balance - $totalDebitWithCommission;
                $lockedWallet->save();

                // Creation de la campagne — remaining_budget = total_budget
                $campaign = Campaign::create($campaignData);

                // Transaction d'audit : commission MANTOTA
                if ($campaignCommission > 0) {
                    Transaction::create([
                        'user_id'        => $user->id,
                        'type'           => 'fee',
                        'amount_target'  => round($campaignCommission, 2),
                        'gateway_fee'    => 0.00,
                        'mantota_markup' => round($campaignCommission, 2),
                        'amount_total'   => round($campaignCommission, 2),
                        'status'         => 'completed',
                        'reference'      => 'CAMP-FEE-' . $campaign->id . '-' . now()->timestamp,
                        'description'    => 'Commission MANTOTA — Lancement campagne #' . $campaign->id,
                    ]);
                }
            });

            Cache::forget('admin.dashboard');
        } catch (\RuntimeException $exception) {
            Storage::disk('public')->delete($mediaPath);

            return back()->withErrors([
                'total_budget' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('vendor.dashboard')
            ->with('success', 'Campagne lancee avec succes ! Le budget a ete debite de votre solde.');
    }

    // ──────────────────────────────────────────────
    //  Edition d'une campagne (textes & lien uniquement)
    // ──────────────────────────────────────────────

    public function edit(Campaign $campaign): InertiaResponse
    {
        $this->authorizeVendor($campaign);

        return Inertia::render('Campaigns/Edit', [
            'campaign'         => [
                'id'               => $campaign->id,
                'title'            => $campaign->title,
                'target_url'       => $campaign->target_url,
                'niche'            => $campaign->niche,
                'target_country'   => $campaign->target_country,
                'click_price'      => (float) $campaign->click_price,
                'platforms'        => $campaign->platforms ?? [],
                'total_budget'     => (float) $campaign->total_budget,
                'remaining_budget' => (float) $campaign->remaining_budget,
                'status'           => $campaign->status->value,
                'media_path'       => $campaign->media_path,
                'media_type'       => $campaign->media_type,
                'instructions'     => $campaign->instructions,
                'rejection_reason' => $campaign->rejection_reason,
            ],
            'available_niches' => Niche::options(),
            'countries'        => self::TARGET_COUNTRIES,
            'country_names'    => self::COUNTRY_NAMES,
            'platforms'        => self::ALLOWED_PLATFORMS,
            'min_cpc'          => (int) mantota_setting('min_cpc_price', self::MIN_CLICK_PRICE),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizeVendor($campaign);

        $minCpc = (int) mantota_setting('min_cpc_price', self::MIN_CLICK_PRICE);

        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'target_url'     => ['required', 'url', 'max:2048'],
            'niche'          => ['required', 'string', Rule::in(Niche::values())],
            'target_country'   => ['required', 'array', 'min:1'],
            'target_country.*' => [Rule::in(self::TARGET_COUNTRIES)],
            'click_price'    => ['required', 'numeric', 'min:' . $minCpc],
            'platforms'      => ['required', 'array', 'min:1'],
            'platforms.*'    => ['string', Rule::in(self::ALLOWED_PLATFORMS)],
            'media'          => ['nullable', 'file', 'mimetypes:' . self::ALLOWED_MIMES, 'max:51200'],
            'instructions'   => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required'          => 'Le titre est obligatoire.',
            'target_url.required'     => 'L\'URL de destination est obligatoire.',
            'target_url.url'          => 'L\'URL de destination doit etre un lien valide.',
            'niche.required'          => 'La niche est obligatoire.',
            'target_country.required' => 'Selectionnez au moins un pays cible.',
            'target_country.min'      => 'Selectionnez au moins un pays cible.',
            'click_price.required'    => 'Le CPC est obligatoire.',
            'click_price.min'         => 'Le CPC minimum est de ' . $minCpc . ' FCFA.',
            'platforms.required'      => 'Selectionnez au moins un reseau social.',
            'media.mimetypes'         => 'Format accepte : JPG, PNG (images) ou MP4, MOV (videos).',
            'media.max'               => 'Le fichier ne doit pas depasser 50 Mo.',
        ]);

        // ── Robot Douanier : scan mots interdits sur modification ──
        $bannedKeywords = mantota_setting('banned_keywords', []);
        if (is_string($bannedKeywords)) {
            $bannedKeywords = json_decode($bannedKeywords, true) ?: [];
        }

        if (! empty($bannedKeywords)) {
            $textToScan = mb_strtolower($validated['title']);
            if ($campaign->product_id) {
                $productName = Product::where('id', $campaign->product_id)->value('name') ?? '';
                $textToScan .= ' ' . mb_strtolower($productName);
            }
            if (! empty($validated['instructions'])) {
                $textToScan .= ' ' . mb_strtolower($validated['instructions']);
            }

            foreach ($bannedKeywords as $keyword) {
                $keyword = trim($keyword);
                if ($keyword !== '' && mb_strpos($textToScan, mb_strtolower($keyword)) !== false) {
                    $campaign->status           = CampaignStatus::REJECTED;
                    $campaign->rejection_reason  = 'Mot interdit detecte lors de la modification : ' . $keyword;
                    $campaign->save();

                    // Expirer tous les SmartLinks associes pour retirer la campagne chez les créateurs de contenu
                    SmartLink::where('campaign_id', $campaign->id)
                        ->where('expires_at', '>', now())
                        ->update(['expires_at' => now()]);

                    $campaign->vendor->notify(new CampaignRejectedNotification($campaign, $campaign->rejection_reason));

                    return redirect()
                        ->route('vendor.campaigns.index')
                        ->with('error', 'Campagne rejetee automatiquement : Mot interdit detecte (' . $keyword . ').');
                }
            }
        }

        // Si la campagne etait rejetee et que le scan est propre, remettre en ACTIVE
        if ($campaign->status === CampaignStatus::REJECTED) {
            $campaign->status           = CampaignStatus::ACTIVE;
            $campaign->rejection_reason = null;
        }

        $campaign->title          = $validated['title'];
        $campaign->target_url     = $validated['target_url'];
        $campaign->niche          = $validated['niche'];
        $campaign->target_country = $validated['target_country'];
        $campaign->click_price    = $validated['click_price'];
        $campaign->platforms      = $validated['platforms'];
        $campaign->instructions   = $validated['instructions'] ?? null;

        // Upload nouveau media si fourni
        if ($request->hasFile('media')) {
            // Supprimer l'ancien media
            if ($campaign->media_path) {
                Storage::disk('public')->delete($campaign->media_path);
            }

            $uploadedFile = $request->file('media');
            $mimeType     = $uploadedFile->getMimeType();
            $campaign->media_type = str_starts_with($mimeType, 'video/') ? 'video' : 'image';
            $campaign->media_path = $uploadedFile->store('campaigns/media', 'public');
        }

        $campaign->save();

        return redirect()
            ->route('vendor.campaigns.show', $campaign)
            ->with('success', 'Campagne mise a jour avec succes.');
    }

    // ──────────────────────────────────────────────
    //  Calcul automatique des places
    // ──────────────────────────────────────────────

    /**
     * Retourne le % de commission effectif (avec reduction ambassadeur le cas echeant).
     */
    private function effectiveCommissionPercent($user): float
    {
        $percent = (float) mantota_setting('campaign_commission_percent', 5);

        if ($user->is_ambassador) {
            $discount = (float) mantota_setting('ambassador_commission_discount', 50);
            $percent  = round($percent * (1 - $discount / 100), 2);
        }

        return $percent;
    }

    /**
     * Calcule le nombre max de participants selon budget / cout moyen par participation du palier.
     * Retourne null pour le mode OPEN (pas de limite).
     */
    private function calculateMaxParticipants(float $budget, string $mode, CampaignTier $tier): ?int
    {
        if ($mode === ParticipationMode::OPEN->value) {
            return null;
        }

        $costs = CampaignTier::costPerParticipation();
        $costPerParticipation = $costs[$tier->value] ?? 2_000;

        if ($costPerParticipation <= 0) {
            return null;
        }

        return max(1, (int) floor($budget / $costPerParticipation));
    }

    // ──────────────────────────────────────────────
    //  Autorisation
    // ──────────────────────────────────────────────

    private function authorizeVendor(Campaign $campaign): void
    {
        abort_unless((int) $campaign->vendor_id === (int) auth()->id(), 403);
    }
}
