<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClickLog extends Model
{
    protected $fillable = [
        'smart_link_id',
        'ip_address',
        'device_id',
        'user_agent_hash',
        'clicker_country',
        'is_vpn',
        'is_paid',
        'is_valid',
        'invalid_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_vpn'   => 'boolean',
            'is_paid'  => 'boolean',
            'is_valid' => 'boolean',
        ];
    }

    public function smartLink(): BelongsTo
    {
        return $this->belongsTo(SmartLink::class);
    }
}
