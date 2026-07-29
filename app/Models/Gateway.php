<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'public_key',
        'secret_key',
        'webhook_secret',
        'environment',
        'countries',
        'payin_fee',
        'payout_fee',
        'priority',
        'supports_refund',
        'supports_payout',
        'extra_config',
    ];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'secret_key'      => 'encrypted',
            'webhook_secret'  => 'encrypted',
            'countries'       => 'array',
            'payin_fee'       => 'decimal:2',
            'payout_fee'      => 'decimal:2',
            'priority'        => 'integer',
            'supports_refund' => 'boolean',
            'supports_payout' => 'boolean',
            'extra_config'    => 'array',
        ];
    }

    /**
     * Does this gateway cover the given country code?
     */
    public function coversCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), $this->countries ?? [], true);
    }
}
