<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Setting — Reglages globaux de la plateforme MANTOTA.
 *
 * Chaque reglage est stocke sous forme key/value avec un type
 * (string, integer, float, boolean, json) pour le cast automatique.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Recupere la valeur d'un reglage par sa cle, avec mise en cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::rememberForever("setting.{$key}", function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Definit ou met a jour un reglage.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'type' => $type],
        );

        Cache::forget("setting.{$key}");
    }

    /**
     * Vide tout le cache des reglages (utile apres update direct en DB).
     */
    public static function clearAll(): void
    {
        static::pluck('key')->each(fn (string $key) => Cache::forget("setting.{$key}"));
    }

    /**
     * Cast la valeur brute selon le type declare.
     */
    private static function castValue(?string $raw, string $type): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $raw,
            'float'   => (float) $raw,
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($raw, true),
            default   => $raw,
        };
    }
}
