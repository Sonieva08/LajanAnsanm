<?php

namespace App\Models;

use App\Support\Entitlements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Announcement extends Model
{
    public const TYPES = ['info', 'warning', 'danger'];
    public const AUDIENCES = ['all', 'free', 'paid'];

    protected $fillable = ['title', 'message', 'type', 'audience', 'starts_at', 'ends_at', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    // Anons visibles par cet utilisateur, selon son état d'abonnement
    public static function visibleTo(User $user): Collection
    {
        $paid = in_array(Entitlements::for($user)->state, ['active', 'grace'], true);

        return static::live()->orderByDesc('id')->get()->filter(fn (self $a) => match ($a->audience) {
            'paid' => $paid,
            'free' => ! $paid,
            default => true,
        })->values();
    }
}
