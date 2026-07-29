<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product — Produit cree par un vendeur (physique ou digital).
 *
 *  - Physique : possede un stock, pas d'access_url.
 *  - Digital  : possede un access_url, pas de stock.
 *  - commission_percent : pourcentage reverse au créateur de contenu sur chaque vente.
 */
class Product extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'vendor_id',
        'name',
        'type',
        'description',
        'price',
        'commission_percent',
        'stock',
        'delivery_type',
        'delivery_fee',
        'access_url',
        'digital_delivery_type',
        'digital_file_path',
        'image_path',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price'              => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'stock'              => 'integer',
            'delivery_fee'       => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations
    // ──────────────────────────────────────────────

    /** Vendeur proprietaire du produit. */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /** Images du produit (galerie multi-images). */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /** Commandes liees a ce produit (relation 1-N). */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    public function isPhysical(): bool
    {
        return $this->type === 'physical';
    }

    public function isDigital(): bool
    {
        return $this->type === 'digital';
    }
}
