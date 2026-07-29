<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\CampaignTier;
use App\Enums\ParticipationMode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campaign — Campagne publicitaire créée par un vendor.
 *
 *  • Contient les métadonnées (titre, description, URL cible).
 *  • Gère le budget total alloué par le vendor.
 *  • Suit un cycle de vie : DRAFT → ACTIVE → PAUSED → COMPLETED.
 *  • Possède N smart_links distribués aux créateurs de contenu.
 */
class Campaign extends Model
{
    use HasFactory;

    /**
     * Attributs modifiables en masse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'product_id',
        'title',
        'target_url',
        'media_path',
        'media_type',
        'total_budget',
        'click_price',
        'remaining_budget',
        'status',
        'tier',
        'open_sea',
        'restricted_circle',
        'commission_percent',
        'target_country',
        'platforms',
        'niche',
        'instructions',
        'is_system_campaign',
        'paused_at',
        'rejection_reason',
        'participation_mode',
        'max_participants',
        'current_participants',
    ];

    /**
     * Casts des attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_budget'       => 'decimal:2',
            'click_price'        => 'decimal:2',
            'remaining_budget'   => 'decimal:2',
            'status'             => CampaignStatus::class,
            'tier'               => CampaignTier::class,
            'open_sea'           => 'boolean',
            'restricted_circle'  => 'boolean',
            'is_system_campaign' => 'boolean',
            'commission_percent' => 'decimal:2',
            'target_country'     => 'array',
            'platforms'          => 'array',
            'paused_at'          => 'datetime',
            'participation_mode'  => ParticipationMode::class,
            'max_participants'    => 'integer',
            'current_participants'=> 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations Eloquent
    // ──────────────────────────────────────────────

    /** Vendor propriétaire de la campagne (relation N-1). */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /** Produit lie a la campagne (Partenariat). */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Smart links rattaches a cette campagne (relation 1-N). */
    public function smartLinks(): HasMany
    {
        return $this->hasMany(SmartLink::class);
    }

    /** Commandes issues de cette campagne (relation 1-N). */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ──────────────────────────────────────────────
    //  Accesseurs
    // ──────────────────────────────────────────────

    /**
     * CPC effectif : click_price × multiplicateur si Cercle Restreint.
     */
    protected function effectiveClickPrice(): Attribute
    {
        return Attribute::get(function () {
            $base = (float) $this->click_price;

            if ($this->restricted_circle) {
                return round($base * CampaignTier::restrictedCircleMultiplier(), 2);
            }

            return $base;
        });
    }

    protected $appends = ['effective_click_price'];
}
