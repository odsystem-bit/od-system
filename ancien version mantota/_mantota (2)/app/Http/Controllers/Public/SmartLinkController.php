<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\ClickLog;
use App\Models\SmartLink;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * SmartLinkController — Pipeline anti-fraude CPC (Device Fingerprint + IP + VPN + Geo).
 *
 * Architecture en 2 etapes :
 *  GET  /go/{hash}       → Page intermediaire : collecte le fingerprint device cote client.
 *  POST /go/{hash}/click → Pipeline de validation complet avant paiement du clic.
 *
 * Pipeline de validation (ordre strict, du moins couteux au plus couteux) :
 *  1. Lien valide & campagne active
 *  2. Anti-Bot (User-Agent + timing)
 *  3. Device Fingerprint dedup (1 device_id / 24h / campagne)
 *  4. IP dedup fallback (si pas de device_id)
 *  5. Detection VPN/Proxy/Datacenter (via ASN)
 *  6. Geo-Fencing (pays cible)
 *  7. Budget suffisant
 *  8. Transaction atomique (lockForUpdate + TOCTOU guard)
 */
class SmartLinkController extends Controller
{
    /**
     * Mapping nom de pays (stocke en BDD) → code ISO 3166-1 alpha-2.
     */
    private const COUNTRY_TO_ISO = [
        'Benin'              => 'BJ',
        'Togo'               => 'TG',
        "Cote d'Ivoire"      => 'CI',
        'Senegal'            => 'SN',
        'Cameroun'           => 'CM',
        'Burkina Faso'       => 'BF',
        'Mali'               => 'ML',
        'Niger'              => 'NE',
        'Guinee'             => 'GN',
        'Gabon'              => 'GA',
        'Congo-Brazzaville'  => 'CG',
        'RD Congo'           => 'CD',
        'Tchad'              => 'TD',
        'Centrafrique'       => 'CF',
        'Mauritanie'         => 'MR',
        'Djibouti'           => 'DJ',
        'Comores'            => 'KM',
        'Madagascar'         => 'MG',
        'Burundi'            => 'BI',
        'Rwanda'             => 'RW',
        'France'             => 'FR',
        'Canada'             => 'CA',
        'Suisse'             => 'CH',
        'Luxembourg'         => 'LU',
        'Monaco'             => 'MC',
        'Haiti'              => 'HT',
        'Maroc'              => 'MA',
        'Tunisie'            => 'TN',
        'Maurice'            => 'MU',
        'Seychelles'         => 'SC',
        'Vanuatu'            => 'VU',
        'Guinee equatoriale' => 'GQ',
    ];

    /** Mots-cles identifiant un bot/scraper dans le User-Agent. */
    private const BOT_KEYWORDS = [
        'bot', 'crawler', 'spider', 'slurp', 'wget', 'curl', 'python',
        'scrapy', 'headless', 'phantom', 'selenium', 'puppeteer',
        'httpclient', 'java/', 'go-http', 'libwww', 'mechanize',
        'nutch', 'ahrefsbot', 'semrushbot', 'dotbot', 'mj12bot',
        'bingpreview', 'googlebot', 'yandexbot', 'baiduspider',
        'facebookexternalhit', 'twitterbot', 'applebot', 'linkedinbot',
    ];

    /** Mots-cles ASN/Org identifiant un datacenter/VPN/proxy. */
    private const DATACENTER_KEYWORDS = [
        'digitalocean', 'amazon', 'aws', 'google cloud', 'microsoft azure',
        'ovh', 'hetzner', 'linode', 'vultr', 'cloudflare', 'akamai',
        'fastly', 'oracle cloud', 'ibm cloud', 'alibaba cloud',
        'rackspace', 'contabo', 'scaleway', 'upcloud', 'kamatera',
        'hosting', 'datacenter', 'data center', 'colocation',
        'vpn', 'proxy', 'tunnel', 'tor exit', 'relay',
        'nord', 'express', 'surfshark', 'cyberghost', 'private internet',
        'mullvad', 'proton', 'hide.me', 'windscribe',
    ];

    /** Seuil minimum de temps de collecte fingerprint (ms). Sous ce seuil = suspect. */
    private const MIN_COLLECT_TIME_MS = 80;

    // ══════════════════════════════════════════════════════════════════
    //  ETAPE 1 : GET /go/{hash} — Page intermediaire (fingerprint)
    // ══════════════════════════════════════════════════════════════════

    /**
     * Affiche la page intermediaire qui collecte le fingerprint device.
     * Aucun traitement CPC ici — juste le rendu de la page de collecte.
     */
    public function gate(Request $request, string $hash): RedirectResponse|\Inertia\Response
    {
        $link = SmartLink::with(['campaign.vendor', 'influencer'])
            ->where('unique_hash', $hash)
            ->first();

        if ($link === null) {
            return redirect()->route('home');
        }

        $campaign = $link->campaign;

        // Inject SEO for OG tags (WhatsApp / social previews)
        $request->attributes->set('seo', $this->buildSeoData($campaign));

        // Si le lien est expire ou la campagne inactive → page expiration
        if (! $link->isValid() || $campaign->status->value !== 'active') {
            return Inertia::render('Public/OfferExpired');
        }

        // Rendu de la page intermediaire (collecte fingerprint JS)
        return Inertia::render('Public/SmartLinkGate', [
            'hash'           => $hash,
            'campaign_title' => $campaign->name ?? 'Offre MANTOTA',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    //  ETAPE 2 : POST /go/{hash}/click — Pipeline de validation CPC
    // ══════════════════════════════════════════════════════════════════

    /**
     * Pipeline complet de validation anti-fraude et paiement CPC.
     *
     * Ordre des verifications (du moins couteux au plus couteux) :
     *  1. Lien & campagne valides
     *  2. Anti-Bot (UA + timing)
     *  3. Device Fingerprint dedup (24h / campagne)
     *  4. IP dedup fallback
     *  5. VPN/Proxy detection (ASN)
     *  6. Geo-Fencing
     *  7. Budget
     *  8. Transaction atomique
     */
    public function processClick(Request $request, string $hash): RedirectResponse|\Inertia\Response
    {
        // ── Charger le SmartLink ──
        $link = SmartLink::with(['campaign.vendor', 'influencer'])
            ->where('unique_hash', $hash)
            ->first();

        if ($link === null) {
            return redirect()->route('home');
        }

        $campaign   = $link->campaign;
        $influencer = $link->influencer;
        $ip         = $request->ip();
        $ua         = (string) $request->userAgent();
        $uaLower    = strtolower($ua);
        $uaHash     = hash('sha256', $ua);
        $deviceId   = $request->input('device_id');
        $collectMs  = (int) $request->input('collect_time_ms', 0);

        // URL de redirection finale
        $redirectUrl = $this->buildRedirectUrl($campaign, $influencer);

        // Inject SEO (au cas ou Inertia render est necessaire)
        $request->attributes->set('seo', $this->buildSeoData($campaign));

        // ── Barriere 0 : Lien expire ou campagne inactive ──
        if (! $link->isValid() || $campaign->status->value !== 'active') {
            return Inertia::render('Public/OfferExpired');
        }

        // ══════════════════════════════════════════════════════════════
        // Barriere 1 : Anti-Bot (User-Agent + timing)
        // ══════════════════════════════════════════════════════════════
        $isBot = $this->detectBot($uaLower);

        // Timing suspect : un humain met au moins ~80ms pour le fingerprinting
        // collectMs == 0 signifie que le champ n'a pas ete envoye (fallback sans JS)
        $hasDeviceId = $deviceId !== null && strlen($deviceId) === 64;
        $isTooFast = $hasDeviceId && $collectMs < self::MIN_COLLECT_TIME_MS;

        if ($isBot || $isTooFast) {
            $reason = $isBot ? 'bot_detected' : 'suspicious_timing';
            $this->logInvalidClick($link, $ip, $deviceId, $uaHash, null, false, $reason);
            return redirect()->away($redirectUrl);
        }

        // ══════════════════════════════════════════════════════════════
        // Barriere 2 : Device Fingerprint dedup (1 device / 24h / campagne)
        // ══════════════════════════════════════════════════════════════
        $campaignLinkIds = SmartLink::where('campaign_id', $campaign->id)->pluck('id');

        if ($deviceId !== null && strlen($deviceId) === 64) {
            $deviceAlreadyClicked = ClickLog::whereIn('smart_link_id', $campaignLinkIds)
                ->where('device_id', $deviceId)
                ->where('is_valid', true)
                ->exists();

            if ($deviceAlreadyClicked) {
                $this->logInvalidClick($link, $ip, $deviceId, $uaHash, null, false, 'duplicate_device');
                return redirect()->away($redirectUrl);
            }
        }

        // ── Barriere 2b : IP dedup fallback (a vie, pour les clics sans fingerprint) ──
        $ipAlreadyClicked = ClickLog::whereIn('smart_link_id', $campaignLinkIds)
            ->where('ip_address', $ip)
            ->where('is_valid', true)
            ->exists();

        if ($ipAlreadyClicked) {
            $this->logInvalidClick($link, $ip, $deviceId, $uaHash, null, false, 'duplicate_ip');
            return redirect()->away($redirectUrl);
        }

        // ══════════════════════════════════════════════════════════════
        // Barriere 3 : Detection VPN/Proxy + Geo-Fencing (1 seul appel API)
        // ══════════════════════════════════════════════════════════════
        $geoData = $this->resolveIpIntel($ip);
        $clickerCountry = $geoData['country_code'];
        $isVpn = $geoData['is_vpn'];

        if ($isVpn) {
            $this->logInvalidClick($link, $ip, $deviceId, $uaHash, $clickerCountry, true, 'vpn_detected');
            return redirect()->away($redirectUrl);
        }

        // ── Barriere 4 : Geo-Fencing ──
        $targetIsos = $this->buildTargetIsos($campaign);

        $geoMismatch = ! empty($targetIsos)
            && $clickerCountry !== null
            && ! in_array($clickerCountry, $targetIsos, true);

        if ($geoMismatch) {
            $this->logInvalidClick($link, $ip, $deviceId, $uaHash, $clickerCountry, $isVpn, 'geo_mismatch');
            return redirect()->away($redirectUrl);
        }

        // ══════════════════════════════════════════════════════════════
        // Barriere 5 : Budget suffisant
        // ══════════════════════════════════════════════════════════════
        $clickPrice = (float) $campaign->effective_click_price;

        if ($clickPrice <= 0 || (float) $campaign->remaining_budget < $clickPrice) {
            if ($clickPrice > 0 && $campaign->status === CampaignStatus::ACTIVE) {
                $campaign->update(['status' => CampaignStatus::COMPLETED]);
                $this->refundRemainingBudget($campaign);
            }
            $this->logInvalidClick($link, $ip, $deviceId, $uaHash, $clickerCountry, $isVpn, 'budget_exhausted');
            return redirect()->away($redirectUrl);
        }

        // ══════════════════════════════════════════════════════════════
        // Barriere 6 : Transaction atomique (lockForUpdate + TOCTOU guard)
        // ══════════════════════════════════════════════════════════════
        $paid = false;

        DB::transaction(function () use (
            $link, $campaign, $influencer, $ip, $clickPrice,
            $clickerCountry, $campaignLinkIds, $deviceId, $uaHash, $isVpn,
            &$paid
        ): void {
            /** @var Campaign $locked */
            $locked = Campaign::where('id', $campaign->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Re-verifier budget sous verrou
            if ((float) $locked->remaining_budget < $clickPrice) {
                return;
            }

            // ── TOCTOU Guard : re-verifier device + IP sous verrou ──
            $duplicateQuery = ClickLog::whereIn('smart_link_id', $campaignLinkIds)
                ->where('is_valid', true);

            if ($deviceId !== null && strlen($deviceId) === 64) {
                // Priorite au device_id pour la dedup (a vie par campagne)
                $alreadyPaid = (clone $duplicateQuery)
                    ->where('device_id', $deviceId)
                    ->exists();
            } else {
                $alreadyPaid = false;
            }

            // Toujours verifier l'IP aussi (double protection)
            if (! $alreadyPaid) {
                $alreadyPaid = (clone $duplicateQuery)
                    ->where('ip_address', $ip)
                    ->exists();
            }

            if ($alreadyPaid) {
                $this->logInvalidClick($link, $ip, $deviceId, $uaHash, $clickerCountry, $isVpn, 'duplicate_race');
                return;
            }

            /** @var Wallet $wallet */
            $wallet = Wallet::where('user_id', $influencer->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Deduction budget campagne
            $locked->remaining_budget = round((float) $locked->remaining_budget - $clickPrice, 2);

            if ((float) $locked->remaining_budget < $clickPrice) {
                $locked->status = CampaignStatus::COMPLETED;
                $this->refundRemainingBudgetLocked($locked);
            }

            $locked->save();

            // Credit wallet influenceur
            $wallet->balance = round((float) $wallet->balance + $clickPrice, 2);
            $wallet->save();

            // Transaction d'audit
            Transaction::create([
                'user_id'        => $influencer->id,
                'type'           => 'earning',
                'amount_target'  => round($clickPrice, 2),
                'gateway_fee'    => 0.00,
                'mantota_markup' => 0.00,
                'amount_total'   => round($clickPrice, 2),
                'status'         => 'completed',
                'reference'      => 'CPC-CLICK-' . $link->id . '-' . now()->timestamp,
                'description'    => 'Gain CPC — Clic valide sur SmartLink #' . $link->id,
            ]);

            // Enregistrer le clic paye et valide
            ClickLog::create([
                'smart_link_id'   => $link->id,
                'ip_address'      => $ip,
                'device_id'       => $deviceId,
                'user_agent_hash' => $uaHash,
                'clicker_country' => $clickerCountry,
                'is_vpn'          => $isVpn,
                'is_paid'         => true,
                'is_valid'        => true,
            ]);

            $paid = true;
        });

        if ($paid) {
            Cache::forget('admin.dashboard');
        }

        return redirect()->away($redirectUrl);
    }

    // ══════════════════════════════════════════════════════════════════
    //  METHODES PRIVEES
    // ══════════════════════════════════════════════════════════════════

    /**
     * Detecte si le User-Agent correspond a un bot/scraper connu.
     */
    private function detectBot(string $uaLower): bool
    {
        if ($uaLower === '') {
            return true;
        }

        foreach (self::BOT_KEYWORDS as $keyword) {
            if (str_contains($uaLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resout les informations IP : pays + detection VPN/datacenter.
     * Utilise ipapi.co avec le champ org pour la detection ASN.
     *
     * @return array{country_code: string|null, is_vpn: bool}
     */
    private function resolveIpIntel(string $ip): array
    {
        $default = ['country_code' => null, 'is_vpn' => false];

        // IPs locales / privees
        if (in_array($ip, ['127.0.0.1', '::1'], true)
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.')
            || str_starts_with($ip, '172.')) {
            return $default;
        }

        try {
            $response = Http::timeout(3)
                ->connectTimeout(2)
                ->get("https://ipapi.co/{$ip}/json/");

            if (! $response->successful() || $response->json('error')) {
                return $default;
            }

            $data = $response->json();
            $countryCode = $data['country_code'] ?? null;

            // Detection VPN/Datacenter via le champ "org"
            $org = strtolower($data['org'] ?? '');
            $isVpn = false;

            foreach (self::DATACENTER_KEYWORDS as $keyword) {
                if (str_contains($org, $keyword)) {
                    $isVpn = true;
                    break;
                }
            }

            return [
                'country_code' => $countryCode,
                'is_vpn'       => $isVpn,
            ];
        } catch (\Throwable $e) {
            Log::warning('SmartLink: echec resolution IP intel', [
                'ip'    => $ip,
                'error' => $e->getMessage(),
            ]);
            return $default;
        }
    }

    /**
     * Construit la liste des codes ISO cibles de la campagne.
     *
     * @return list<string>
     */
    private function buildTargetIsos(Campaign $campaign): array
    {
        $targetCountries = is_array($campaign->target_country)
            ? $campaign->target_country
            : [$campaign->target_country];

        return array_values(array_filter(
            array_map(function ($c) {
                if (is_string($c) && strlen($c) === 2 && ctype_alpha($c)) {
                    return strtoupper($c);
                }
                return self::COUNTRY_TO_ISO[$c] ?? null;
            }, $targetCountries)
        ));
    }

    /**
     * Construit l'URL de redirection vers la boutique du vendeur.
     */
    private function buildRedirectUrl(Campaign $campaign, $influencer): string
    {
        $vendor = $campaign->vendor;

        if ($vendor?->slug && $campaign->product_id) {
            return route('shop.show', ['vendorSlug' => $vendor->slug])
                . '?product=' . $campaign->product_id
                . '&ref=' . $influencer->id
                . '&campaign=' . $campaign->id;
        }

        if ($vendor?->slug) {
            return route('shop.show', ['vendorSlug' => $vendor->slug])
                . '?ref=' . $influencer->id
                . '&campaign=' . $campaign->id;
        }

        return route('home');
    }

    /**
     * Construit les metadonnees SEO pour les previews OG (WhatsApp, etc.).
     */
    private function buildSeoData(Campaign $campaign): array
    {
        $ogImage = null;
        if ($campaign->vendor && $campaign->vendor->products()->exists()) {
            $firstProduct = $campaign->vendor->products()->with('images')->first();
            $ogImage = $firstProduct?->images?->first()?->path
                ? asset('storage/' . $firstProduct->images->first()->path)
                : null;
        }

        return [
            'title'       => $campaign->name ?? 'Offre exclusive sur MANTOTA',
            'description' => 'Decouvrez cette offre exclusive. Cliquez pour en profiter !',
            'image'       => $ogImage,
        ];
    }

    /**
     * Enregistre un clic invalide avec tous les details de diagnostic.
     */
    private function logInvalidClick(
        SmartLink $link,
        string $ip,
        ?string $deviceId,
        string $uaHash,
        ?string $clickerCountry,
        bool $isVpn,
        string $reason
    ): void {
        ClickLog::create([
            'smart_link_id'   => $link->id,
            'ip_address'      => $ip,
            'device_id'       => $deviceId,
            'user_agent_hash' => $uaHash,
            'clicker_country' => $clickerCountry,
            'is_vpn'          => $isVpn,
            'is_paid'         => false,
            'is_valid'        => false,
            'invalid_reason'  => $reason,
        ]);

        // Auto-lock : si l'influenceur accumule trop de clics frauduleux,
        // verrouiller son portefeuille automatiquement.
        $this->checkAutoLock($link);
    }

    /**
     * Verrouille auto le wallet de l'influenceur si >= 50 clics
     * frauduleux en 24h sur ses liens personnels.
     */
    private function checkAutoLock(SmartLink $link): void
    {
        $influencer = $link->influencer;
        if ($influencer === null) {
            return;
        }

        $threshold = 50;

        $linkIds = SmartLink::where('influencer_id', $influencer->id)->pluck('id');

        $fraudCount = ClickLog::whereIn('smart_link_id', $linkIds)
            ->where('is_valid', false)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($fraudCount < $threshold) {
            return;
        }

        $wallet = Wallet::where('user_id', $influencer->id)->first();
        if ($wallet === null || $wallet->is_locked) {
            return;
        }

        $wallet->forceFill([
            'is_locked'   => true,
            'lock_reason' => "Auto-lock : {$fraudCount} clics frauduleux en 24h",
            'locked_at'   => now(),
        ])->save();

        Log::warning('Wallet auto-locked', [
            'user_id'     => $influencer->id,
            'fraud_count' => $fraudCount,
        ]);
    }

    /**
     * Rembourse le remaining_budget au vendor (hors transaction DB existante).
     */
    private function refundRemainingBudget(Campaign $campaign): void
    {
        $refund = (float) $campaign->remaining_budget;
        if ($refund <= 0) {
            return;
        }

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
                'reference'      => 'CAMP-REFUND-' . $locked->id . '-' . now()->timestamp,
                'description'    => 'Remboursement budget restant — Campagne #' . $locked->id . ' terminee',
            ]);
        });
    }

    /**
     * Rembourse le remaining_budget au vendor (a l'interieur d'une transaction DB, campagne deja verrouillee).
     */
    private function refundRemainingBudgetLocked(Campaign $lockedCampaign): void
    {
        $amount = (float) $lockedCampaign->remaining_budget;
        if ($amount <= 0) {
            return;
        }

        $lockedCampaign->remaining_budget = 0;

        $wallet = Wallet::where('user_id', $lockedCampaign->vendor_id)->lockForUpdate()->firstOrFail();
        $wallet->balance = round((float) $wallet->balance + $amount, 2);
        $wallet->save();

        Transaction::create([
            'user_id'        => $lockedCampaign->vendor_id,
            'type'           => 'refund',
            'amount_target'  => round($amount, 2),
            'gateway_fee'    => 0.00,
            'mantota_markup' => 0.00,
            'amount_total'   => round($amount, 2),
            'status'         => 'completed',
            'reference'      => 'CAMP-REFUND-' . $lockedCampaign->id . '-' . now()->timestamp,
            'description'    => 'Remboursement budget restant — Campagne #' . $lockedCampaign->id . ' terminee',
        ]);
    }
}
