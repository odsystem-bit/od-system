<?php

declare(strict_types=1);

use App\Models\Setting;

if (! function_exists('mantota_setting')) {
    /**
     * Recupere un reglage global MANTOTA par sa cle.
     *
     * Usage : mantota_setting('withdrawal_fee_percent', 20)
     */
    function mantota_setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}
