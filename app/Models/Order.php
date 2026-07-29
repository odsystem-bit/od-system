<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Order — Commande e-commerce avec sequestre (Escrow) MANTOTA.
 *
 * Cycle de vie :
 *  1. PENDING   : Paiement simule OK — argent bloque en escrow.
 *  2. SHIPPED   : Vendeur marque comme expediee.
 *  3. DELIVERED : Client confirme reception → escrow libere vers balances.
 *  4. DISPUTED  : Le client ouvre un litige → blocage maintenu.
 *  5. CANCELLED : Commande annulee → remboursement.
 *
 * L'argent ne passe JAMAIS directement en balance disponible a la creation.
 * Il transite obligatoirement par escrow_balance sur les wallets concernes.
 */
class Order extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'vendor_id',
        'product_id',
        'influencer_id',
        'campaign_id',
        'customer_name',
        'customer_phone',
        'customer_whatsapp',
        'customer_email',
        'country',
        'city',
        'landmark_indication',
        'delivery_guy_name',
        'delivery_guy_phone',
        'delivery_company',
        'vendor_shipping_note',
        'amount_paid',
        'commission_amount',
        'vendor_earnings',
        'delivery_fee_paid',
        'status',
        'payment_status',
        'payment_gateway_ref',
        'payment_gateway',
        'delivery_deadline',
        'tracking_token',
        'delivery_pin',
        'dispute_reason',
        'vendor_defense_message',
        'vendor_defense_proof',
        'cancel_reason',
    ];

    /**
     * Attributs masques lors de la serialisation JSON.
     * Le delivery_pin ne doit JAMAIS etre visible par le vendeur.
     *
     * @var list<string>
     */
    protected $hidden = [
        'delivery_pin',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_paid'       => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'vendor_earnings'   => 'decimal:2',
            'delivery_fee_paid' => 'decimal:2',
            'status'            => OrderStatus::class,
            'delivery_deadline' => 'datetime',
        ];
    }

    /**
     * Retourne true si le paiement client est confirmé (escrow actif).
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    // ──────────────────────────────────────────────
    //  Relations Eloquent
    // ──────────────────────────────────────────────

    /** Vendeur proprietaire de la commande. */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /** Produit commande. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Créateur de contenu partenaire (nullable). */
    public function influencer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    /** Campagne partenaire liee a cette commande (nullable). */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** Messages du chat de litige. */
    public function disputeMessages(): HasMany
    {
        return $this->hasMany(OrderDisputeMessage::class);
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Genere une reference courte unique (CMD-XXXX).
     */
    public static function generateReference(): string
    {
        do {
            $ref = 'CMD-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }

    /**
     * Verifie si la commande est en retard de livraison.
     */
    public function isOverdue(): bool
    {
        if (! $this->delivery_deadline) {
            return false;
        }

        return now()->greaterThan($this->delivery_deadline)
            && in_array($this->status, [OrderStatus::PENDING, OrderStatus::SHIPPED]);
    }
}
