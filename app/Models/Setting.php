<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

// Réglages modifiables depuis la console propriétaire (sans toucher au code ni au .env)
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 60, fn () => static::where('key', $key)->first()?->value) ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    // Règles d'abonnement modifiables depuis la console
    public static function subscriptionRules(): array
    {
        $r = array_merge(['grace_days' => 7, 'remind_days' => 5], (array) static::get('subscription', []));

        return ['grace_days' => max(0, (int) $r['grace_days']), 'remind_days' => max(0, (int) $r['remind_days'])];
    }

    // Où les clients envoient leur paiement (saisi par le propriétaire)
    public static function paymentInfo(): array
    {
        return array_merge(
            ['moncash_number' => null, 'moncash_name' => null, 'natcash_number' => null, 'natcash_name' => null, 'bank_info' => null, 'instructions' => null],
            (array) static::get('payment', [])
        );
    }

    // Méthodes de paiement réellement configurées => libellé
    public static function paymentMethods(): array
    {
        $p = static::paymentInfo();
        $out = [];
        if (filled($p['moncash_number'])) { $out['moncash'] = 'MonCash'; }
        if (filled($p['natcash_number'])) { $out['natcash'] = 'NatCash'; }
        if (filled($p['bank_info'])) { $out['bank'] = 'Bank'; }

        return $out;
    }

    public static function maintenance(): array
    {
        return array_merge(
            ['enabled' => false, 'message' => null, 'ends_at' => null, 'updated_by' => null, 'updated_at' => null],
            (array) static::get('maintenance', [])
        );
    }
}
