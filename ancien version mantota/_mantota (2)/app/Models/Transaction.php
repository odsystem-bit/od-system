<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transaction — Écriture financière avec décomposition complète des marges.
 *
 * Formule :  amount_total = amount_target + gateway_fee + mantota_markup
 *
 * Types :
 *  • deposit    → Rechargement du wallet par le vendor via FedaPay.
 *  • withdrawal → Retrait de fonds par un créateur de contenu via FedaPay.
 *  • earning    → Revenu crédité à un créateur de contenu pour vues validées.
 *  • fee        → Frais prélevés par la plateforme.
 *
 * Aucun stockage JSON — tout transite par MySQL exclusivement.
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * Attributs modifiables en masse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'amount_target',
        'gateway_fee',
        'mantota_markup',
        'amount_total',
        'status',
        'reference',
        'gateway_ref',
        'payment_gateway',
        'momo_number',
        'description',
    ];

    /**
     * Casts — tous les montants en decimal:2 pour la précision financière.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_target'  => 'decimal:2',
            'gateway_fee'    => 'decimal:2',
            'mantota_markup' => 'decimal:2',
            'amount_total'   => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations Eloquent
    // ──────────────────────────────────────────────

    /** Utilisateur propriétaire de cette transaction. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
