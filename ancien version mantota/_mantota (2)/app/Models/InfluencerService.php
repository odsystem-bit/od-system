<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfluencerService extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'influencer_id',
        'title',
        'type',
        'price',
        'duration',
        'description',
        'included_revisions',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relations
    // ──────────────────────────────────────────────

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'service_id');
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    public function isUgcHumain(): bool
    {
        return $this->type === 'ugc_humain';
    }

    public function isVideoPubIa(): bool
    {
        return $this->type === 'video_pub_ia';
    }
}
