<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SmartLink — Lien de tracking unique avec durée de vie de 48 h.
 *
 *  • Relie un créateur de contenu à une campagne via un hash unique.
 *  • proof_url   → URL de la publication (vidéo/post) fournie par le créateur de contenu.
 *  • expires_at  → horodatage d'expiration automatique (création + 48 h).
 *
 * La méthode isValid() permet de vérifier en temps réel si le lien est encore actif.
 */
class SmartLink extends Model
{
    use HasFactory;

    /**
     * Attributs modifiables en masse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'campaign_id',
        'influencer_id',
        'unique_hash',
        'proof_url',
        'expires_at',
    ];

    /**
     * Casts des attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations Eloquent
    // ──────────────────────────────────────────────

    /** Campagne parente (relation N-1). */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** Créateur de contenu propriétaire du lien (relation N-1). */
    public function influencer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    /**
     * Logs de clics enregistres pour ce SmartLink.
     */
    public function clickLogs(): HasMany
    {
        return $this->hasMany(\App\Models\ClickLog::class);
    }

    /**
     * Transactions de type 'earning' liées à ce SmartLink via la référence EARN-LINK-{id}.
     *
     * Utilise une relation conventionnelle basée sur user_id (influencer)
     * avec scope sur la référence unique du lien.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'influencer_id')
            ->where('reference', 'EARN-LINK-' . $this->id)
            ->where('type', 'earning');
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Vérifie si le smart link est encore valide (non expiré).
     *
     * @return bool true si expires_at est dans le futur, false sinon.
     */
    public function isValid(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isFuture();
    }
}
