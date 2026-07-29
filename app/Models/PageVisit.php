<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVisit extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'country',
        'country_code',
        'city',
        'page_url',
        'referrer',
        'device_type',
        'browser',
        'time_spent',
    ];

    protected function casts(): array
    {
        return [
            'time_spent' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
