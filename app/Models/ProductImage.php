<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductImage — Image associee a un produit (galerie multi-images).
 *
 * Chaque produit peut avoir plusieurs images avec un ordre de tri (sort_order).
 * La premiere image (sort_order = 0) sert d'image principale/couverture.
 */
class ProductImage extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'path',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations
    // ──────────────────────────────────────────────

    /** Produit proprietaire de cette image. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
