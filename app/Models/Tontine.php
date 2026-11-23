<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class Tontine extends Model
{
    use HasFactory;

    public const MAX_FEE_PERCENT = 30;

    protected $fillable = [
        'admin_id', 'name', 'amount_per_period', 'organizer_fee_percent', 'period_type', 'start_date', 'status', 'current_round',
        'member_portal_enabled', 'portal_payment',
    ];

    protected function casts(): array
    {
        return [
            'amount_per_period' => 'decimal:2',
            'organizer_fee_percent' => 'decimal:2',
            'start_date' => 'date',
            'member_portal_enabled' => 'boolean',
            'portal_payment' => 'array',
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

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
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

    // Paiements déclarés par les membres (en attente, validés ou refusés)
    public function declarations(): HasManyThrough
    {
        return $this->hasManyThrough(PaymentDeclaration::class, Cycle::class);
    }

    public function payouts(): HasManyThrough
    {
        return $this->hasManyThrough(Payout::class, Cycle::class);
    }

    // Versements que le bénéficiaire dit ne pas avoir reçus
    public function disputedPayoutsCount(): int
    {
        return $this->payouts()->where('payouts.member_response', 'not_received')->count();
    }

    public function pendingDeclarationsCount(): int
    {
        return $this->declarations()->where('payment_declarations.status', 'pending')->count();
    }

    // Coordonnées de paiement affichées aux membres (saisies par le créateur)
    public function portalPayment(): array
    {
        return array_merge(
            [
                'moncash_number' => null, 'moncash_name' => null, 'moncash_qr_path' => null,
                'natcash_number' => null, 'natcash_name' => null, 'natcash_qr_path' => null,
                'bank_info' => null, 'instructions' => null,
            ],
            (array) $this->portal_payment
        );
    }

    // Méthodes réellement configurées => libellé
    public function portalPaymentMethods(): array
    {
        $p = $this->portalPayment();
        $out = [];
        if (filled($p['moncash_qr_path']) && Storage::disk('local')->exists($p['moncash_qr_path'])) {
            $out['moncash'] = 'MonCash';
        }
        if (filled($p['natcash_qr_path']) && Storage::disk('local')->exists($p['natcash_qr_path'])) {
            $out['natcash'] = 'NatCash';
        }
        if (filled($p['bank_info'])) {
            $out['bank'] = 'Bank';
        }

        return $out;
    }
}
