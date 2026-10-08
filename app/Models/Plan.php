<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    public const PERIODS = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12];

    protected $fillable = [
        'slug', 'name', 'description', 'badge', 'is_highlighted', 'is_active', 'sort_order',
        'price_monthly', 'price_quarterly', 'price_yearly',
        'max_active_tontines', 'max_members_per_tontine', 'features',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_highlighted' => 'boolean',
            'is_active' => 'boolean',
            'is_free' => 'boolean',
            'price_monthly' => 'decimal:2',
            'price_quarterly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SubscriptionRequest::class);
    }

    public function scopeListed(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public static function free(): self
    {
        return static::where('is_free', true)->first()
            ?? new static(['slug' => 'gratis', 'name' => 'Gratis', 'max_active_tontines' => 1, 'max_members_per_tontine' => 10, 'features' => []]);
    }

    public function allows(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }

    public function priceFor(string $period): ?float
    {
        $v = $this->getAttribute("price_{$period}");

        return $v !== null ? (float) $v : null;
    }

    // Réduction (en %) du prix trimestriel/annuel par rapport à 3 ou 12 mois au tarif mensuel
    public function discountPercent(string $period): ?int
    {
        $months = self::PERIODS[$period] ?? 1;
        $base = $this->priceFor('monthly');
        $price = $this->priceFor($period);

        if ($months === 1 || ! $base || $price === null || $price >= $base * $months) {
            return null;
        }

        return (int) round((1 - $price / ($base * $months)) * 100);
    }

    public function isRequestable(): bool
    {
        return $this->is_active && ! $this->is_free && ($this->priceFor('monthly') ?? 0) > 0;
    }
}
