<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Wallet — Portefeuille financier d'un utilisateur MANTOTA.
 *
 *  • balance         → fonds disponibles, retirables immédiatement.
 *  • pending_balance → fonds bloqués (campagne en cours, payout en transit…).
 *
 * Toutes les opérations monétaires transitent via la table `transactions` ;
 * ce modèle ne sert qu'à refléter le solde courant.
 */
class Wallet extends Model
{
    use HasFactory;

    /**
     * Attributs modifiables en masse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
        'escrow_balance',
        'referral_balance',
        'is_locked',
        'lock_reason',
        'locked_at',
    ];

    /**
     * Casts des attributs monétaires.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance'          => 'decimal:2',
            'pending_balance'  => 'decimal:2',
            'escrow_balance'   => 'decimal:2',
            'referral_balance' => 'decimal:2',
            'is_locked'        => 'boolean',
            'locked_at'        => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations Eloquent
    // ──────────────────────────────────────────────

    /** Le portefeuille est-il verrouille (fraude / abus) ? */
    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    /** Propriétaire du wallet (relation N-1). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Transactions financieres du proprietaire du wallet. */
    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'user_id');
    }
}
