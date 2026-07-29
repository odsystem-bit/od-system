<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductSale — Vente e-commerce realisee sur le mini-site public.
 *
 * Repartition financiere :
 *  amount_paid       = montant total paye par le client.
 *  commission_amount = part reversee au créateur de contenu partenaire (si present).
 *  vendor_earnings   = part reversee au vendeur.
 *
 * Formule :  amount_paid = commission_amount + vendor_earnings
 */
class ProductSale extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'vendor_id',
        'influencer_id',
        'product_id',
        'amount_paid',
        'commission_amount',
        'vendor_earnings',
        'status',
        'reference',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_paid'       => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'vendor_earnings'   => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations
    // ──────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
