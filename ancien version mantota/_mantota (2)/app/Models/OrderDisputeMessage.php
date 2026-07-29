<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderDisputeMessage — Message dans le chat de litige e-commerce.
 *
 * sender_type : 'customer' | 'vendor' | 'admin'
 * user_id : rempli uniquement pour vendor/admin (le customer est public, sans compte).
 */
class OrderDisputeMessage extends Model
{
    protected $fillable = [
        'order_id',
        'sender_type',
        'user_id',
        'message',
        'is_flagged',
        'original_message',
    ];

    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
