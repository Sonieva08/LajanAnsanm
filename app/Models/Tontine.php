<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tontine extends Model
{
    public const MAX_FEE_PERCENT = 30;

    protected $fillable = [
        'admin_id', 'name', 'amount_per_period', 'organizer_fee_percent', 'period_type', 'start_date', 'status', 'current_round',
    ];

    protected function casts(): array
    {
        return [
            'amount_per_period' => 'decimal:2',
            'organizer_fee_percent' => 'decimal:2',
            'start_date' => 'date',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class)->orderBy('position');
    }

    // Tous les tours de tous les kòl (historique complet)
    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class)->orderBy('round')->orderBy('cycle_number');
    }

    // Tours du kòl en cours uniquement (à appeler sur une instance réelle, pas en eager loading)
    public function roundCycles(): HasMany
    {
        return $this->hasMany(Cycle::class)
            ->where('round', $this->current_round)
            ->orderBy('cycle_number');
    }

    // Cotisations de tous les kòl
    public function contributions(): HasManyThrough
    {
        return $this->hasManyThrough(Contribution::class, Cycle::class);
    }

    // Cotisations du kòl en cours
    public function roundContributions(): Builder
    {
        return Contribution::whereIn('cycle_id', $this->roundCycles()->reorder()->select('id'));
    }

    public function activeMembersCount(): int
    {
        return $this->members()->where('is_active', true)->count();
    }

    public function totalCollected(): float
    {
        return (float) $this->contributions()->sum('contributions.amount');
    }

    public function potAmount(): float
    {
        return (float) $this->amount_per_period * $this->activeMembersCount();
    }

    public function hasFee(): bool
    {
        return (float) $this->organizer_fee_percent > 0;
    }

    // Commission de l'organisateur sur un montant collecté (arrondie au centime)
    public function feeFor(float $gross): float
    {
        return round($gross * (float) $this->organizer_fee_percent / 100, 2);
    }

    // Ce que reçoit le bénéficiaire quand tout le monde a payé
    public function netPotAmount(): float
    {
        $pot = $this->potAmount();

        return $pot - $this->feeFor($pot);
    }

    // Verrouillé quand le kòl en cours a des tours
    public function isLocked(): bool
    {
        return $this->roundCycles()->exists();
    }

    // Tirage au sort des membres actifs (utilisé au relancement)
    public function shuffleMembers(): void
    {
        $ids = $this->members()->where('is_active', true)->pluck('id')->shuffle()->values();

        Member::where('tontine_id', $this->id)->update(['position' => null]);

        foreach ($ids as $i => $id) {
            Member::whereKey($id)->update(['position' => $i + 1]);
        }
    }
}
