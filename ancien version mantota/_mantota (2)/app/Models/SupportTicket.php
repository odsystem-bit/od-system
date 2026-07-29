<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_email',
        'reference_code',
        'subject',
        'category',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public static function generateReference(): string
    {
        do {
            $code = 'TKT-' . random_int(1000, 9999);
        } while (static::where('reference_code', $code)->exists());

        return $code;
    }
}
