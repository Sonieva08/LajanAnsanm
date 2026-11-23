<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    public const DAYS = 14;

    protected $fillable = [
        'tontine_id', 'created_by', 'token_hash', 'phone', 'expires_at',
        'accepted_at', 'accepted_by', 'revoked_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    // Renvoie l'invitation et le lien en clair (à montrer une seule fois)
    public static function issue(Tontine $tontine, User $by, ?string $phone, int $days = self::DAYS): array
    {
        $plain = Str::random(40);

        $invitation = static::create([
            'tontine_id' => $tontine->id,
            'created_by' => $by->id,
            'token_hash' => hash('sha256', $plain),
            'phone' => $phone,
            'expires_at' => now()->addDays($days),
        ]);

        return [$invitation, $plain];
    }

    public static function findByToken(string $plain): ?self
    {
        return static::where('token_hash', hash('sha256', $plain))->first();
    }

    public function isUsable(): bool
    {
        return ! $this->accepted_at
            && ! $this->revoked_at
            && $this->expires_at->isFuture()
            && $this->tontine?->status === 'draft';
    }
}
