<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Member extends Model
{
    public const PAYOUT_METHODS = ['moncash', 'natcash', 'bank', 'cash'];

    protected $fillable = [
        'tontine_id', 'name', 'phone', 'email', 'position', 'is_active', 'payout_method', 'payout_account',
        'payout_name', 'consent_at', 'invited_at', 'invitation_accepted_at',
    ];

    // Jamais exposés (JSON, logs) : le jeton donne accès à l'espace du membre
    protected $hidden = ['access_token', 'access_token_hash'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'consent_at' => 'datetime',
            'invited_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'access_token' => 'encrypted',
            'last_portal_visit_at' => 'datetime',
        ];
    }

    // Libellé d'une méthode de réception (cash = remise en main propre)
    public static function payoutMethodLabel(?string $method): string
    {
        return match ($method) {
            'moncash' => 'MonCash',
            'natcash' => 'NatCash',
            'bank' => __('Labank'),
            'cash' => __('Lajan kach'),
            default => '—',
        };
    }

    public function hasPayoutDestination(): bool
    {
        return filled($this->payout_method) && ($this->payout_method === 'cash' || filled($this->payout_account));
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByAccessToken(string $token): ?self
    {
        if (strlen($token) !== 40) {
            return null;
        }

        return static::with('tontine')->where('access_token_hash', static::hashToken($token))->first();
    }

    // Crée (ou remplace) le lien d'accès : l'ancien lien cesse de fonctionner immédiatement
    public function issueAccessToken(): string
    {
        $token = Str::random(40);
        $this->forceFill(['access_token' => $token, 'access_token_hash' => static::hashToken($token)])->save();

        return $token;
    }

    public function portalUrl(): ?string
    {
        return $this->access_token ? route('portal.enter', $this->access_token) : null;
    }

    public function invitationUrl(): ?string
    {
        return $this->access_token ? route('member.invitation.show', $this->access_token) : null;
    }

    // Numéro pour wa.me : chiffres seulement, indicatif Haïti ajouté si absent
    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if ($digits === '') {
            return null;
        }

        return strlen($digits) === 8 ? '509'.$digits : $digits;
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function hasPaidForCycle(int $cycleId): bool
    {
        return $this->contributions()->where('cycle_id', $cycleId)->exists();
    }

    public function totalPaid(): float
    {
        return (float) $this->contributions()->sum('amount');
    }
}
