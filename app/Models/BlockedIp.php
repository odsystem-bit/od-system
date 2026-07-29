<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'is_permanent',
        'blocked_by',
        'expires_at',
    ];

    protected $casts = [
        'is_permanent' => 'boolean',
        'expires_at'   => 'datetime',
    ];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_permanent', true)
              ->orWhere('expires_at', '>', now());
        });
    }

    public static function isBlocked(string $ip): bool
    {
        return static::where('ip_address', $ip)->active()->exists();
    }
}
