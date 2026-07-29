<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'vendor_id',
        'influencer_id',
        'service_id',
        'product_id',
        'amount',
        'status',
        'brief',
        'video_path',
        'revisions_allowed',
        'revisions_used',
        'revision_feedback',
        'delivered_at',
        'sample_status',
        'sample_delivery_guy_name',
        'sample_delivery_guy_phone',
        'production_started_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'                => 'decimal:2',
            'delivered_at'          => 'datetime',
            'production_started_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    //  Constantes de statut
    // ──────────────────────────────────────────────

    public const STATUS_PENDING            = 'pending';
    public const STATUS_SHOOTING           = 'shooting';
    public const STATUS_DELIVERED          = 'delivered';
    public const STATUS_REVISION_REQUESTED = 'revision_requested';
    public const STATUS_COMPLETED          = 'completed';
    public const STATUS_DISPUTED           = 'disputed';
    public const STATUS_APPROVED           = 'approved';
    public const STATUS_REJECTED           = 'rejected';
    public const STATUS_CANCELLED          = 'cancelled';

    // ── Sample statuses ──
    public const SAMPLE_NOT_REQUIRED    = 'not_required';
    public const SAMPLE_PENDING_SHIPMENT = 'pending_shipment';
    public const SAMPLE_SHIPPED          = 'shipped';
    public const SAMPLE_RECEIVED         = 'received';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SHOOTING,
        self::STATUS_DELIVERED,
        self::STATUS_REVISION_REQUESTED,
        self::STATUS_COMPLETED,
        self::STATUS_DISPUTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

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

    public function service(): BelongsTo
    {
        return $this->belongsTo(InfluencerService::class, 'service_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ServiceOrderMessage::class)->orderBy('created_at');
    }
}
