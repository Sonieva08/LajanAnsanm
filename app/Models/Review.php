<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Review extends Model
{
    public const STATUSES = ['pending', 'approved', 'hidden'];

    // « status » et « user_id » ne sont volontairement PAS ici : impossible de s'auto-approuver depuis un formulaire
    protected $fillable = ['rating', 'comment'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', 'approved');
    }

    // Vie privée : seulement le prénom et l'initiale du nom, jamais l'e-mail
    public function displayName(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->user?->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return __('Itilizatè LajanAnsanm');
        }

        $first = Str::limit($parts[0], 20, '');
        $last = $parts[array_key_last($parts)] ?? null;

        return $last !== null && count($parts) > 1
            ? $first.' '.mb_strtoupper(mb_substr($last, 0, 1)).'.'
            : $first;
    }

    // Note moyenne et nombre d'avis PUBLIÉS (rien n'est inventé : 0 avis = 0)
    public static function publicSummary(): array
    {
        $row = static::approved()->selectRaw('count(*) as c, avg(rating) as a')->first();
        $count = (int) ($row->c ?? 0);

        return ['count' => $count, 'avg' => $count > 0 ? round((float) $row->a, 1) : 0.0];
    }
}
