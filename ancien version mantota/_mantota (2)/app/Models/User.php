<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\VerifyEmailCodeNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User — Modèle central de la plateforme MANTOTA.
 *
 * Rôles possibles : VENDOR (annonceur), INFLUENCER (créateur), ADMIN.
 * Chaque utilisateur possède un wallet, peut créer des campagnes (vendor)
 * et dispose d'un historique complet de transactions financières.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Attributs modifiables en masse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'profile_photo',
        'email',
        'password',
        'role',
        'referral_code',
        'referred_by',
        'country',
        'phone',
        'momo_number',
        'kyc_status',
        'kyc_document_front',
        'kyc_document_back',
        'kyc_document_selfie',
        'birth_date',
        'id_card_expiry',
        'business_name',
        'shop_name',
        'shop_logo_path',
        'ifu_or_rccm',
        'shop_display_format',
        'shop_theme',
        'welcome_popup_seen',
        'tiktok_url',
        'instagram_url',
        'facebook_url',
        'youtube_url',
        'snapchat_url',
        'tiktok_followers',
        'instagram_followers',
        'facebook_followers',
        'youtube_followers',
        'snapchat_followers',
        'socials_updated_at',
        'niches',
        'tier',
        'is_ambassador',
        'ambassador_tier',
        'ambassador_source',
        'ambassador_subscribed_at',
        'ambassador_expires_at',
        'vip_requested_at',
        'email_verification_code',
        'email_verification_code_expires_at',
    ];

    /**
     * Attributs masqués lors de la sérialisation.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts des attributs — utilise les Enums PHP 8.2+.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'kyc_status'        => 'string',
            'is_vip'            => 'boolean',
            'is_banned'         => 'boolean',
            'is_ambassador'     => 'boolean',
            'ambassador_subscribed_at' => 'datetime',
            'ambassador_expires_at'    => 'datetime',
            'welcome_popup_seen' => 'boolean',
            'niches'            => 'array',
            'tier'              => 'string',
            'admin_permissions' => 'array',
            'vip_requested_at'  => 'datetime',
            'birth_date'        => 'date',
            'id_card_expiry'    => 'date',
            'socials_updated_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'referral_count' => 'integer',
            'referral_earnings' => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations Eloquent
    // ──────────────────────────────────────────────

    /** Portefeuille financier (relation 1-1). */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /** Utilisateur qui a parraine celui-ci. */
    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /** Filleuls parraines par cet utilisateur. */
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /** Campagnes créées par ce vendor (relation 1-N). */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'vendor_id');
    }

    /** Historique de toutes les transactions financieres (relation 1-N). */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function influencerServices(): HasMany
    {
        return $this->hasMany(InfluencerService::class, 'influencer_id');
    }

    public function serviceOrdersAsVendor(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'vendor_id');
    }

    public function serviceOrdersAsInfluencer(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'influencer_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function kycLogs(): HasMany
    {
        return $this->hasMany(KycLog::class);
    }

    public function ordersAsVendor(): HasMany
    {
        return $this->hasMany(Order::class, 'vendor_id');
    }

    /** Commandes ou l'utilisateur est le créateur de contenu partenaire (relation 1-N). */
    public function ordersAsInfluencer(): HasMany
    {
        return $this->hasMany(Order::class, 'influencer_id');
    }

    // ──────────────────────────────────────────────
    //  Accesseurs
    // ──────────────────────────────────────────────

    /**
     * Portee totale : somme de tous les abonnes declares sur les reseaux sociaux.
     */
    public function getTotalFollowersAttribute(): int
    {
        return (int) ($this->tiktok_followers ?? 0)
             + (int) ($this->instagram_followers ?? 0)
             + (int) ($this->facebook_followers ?? 0)
             + (int) ($this->youtube_followers ?? 0)
             + (int) ($this->snapchat_followers ?? 0);
    }

    /**
     * Route notification for WhatsApp channel (Evolution API).
     * Returns the cleaned phone number WITHOUT '+' prefix.
     */
    public function routeNotificationForWhatsapp(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $this->phone);

        // If no country code prefix, assume 229 (Benin)
        if (! str_starts_with($phone, '229')) {
            $phone = '229' . $phone;
        }

        return $phone;
    }

    // ──────────────────────────────────────────────
    //  Email verification par code
    // ──────────────────────────────────────────────

    public function generateEmailVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_verification_code' => $code,
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        return $code;
    }

    public function sendEmailVerificationNotification(): void
    {
        $code = $this->generateEmailVerificationCode();
        $this->notify(new VerifyEmailCodeNotification($code));
    }
}
