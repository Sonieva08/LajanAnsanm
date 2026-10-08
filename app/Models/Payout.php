<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = ['cycle_id', 'member_id', 'amount', 'fee_percent', 'fee_amount', 'paid_at', 'confirmed'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',       // total collecté pour le tour (brut)
            'fee_percent' => 'decimal:2',
            'fee_amount' => 'decimal:2',   // commission retenue par l'organisateur
            'paid_at' => 'date',
            'confirmed' => 'boolean',
        ];
    }

    // Montant réellement remis au bénéficiaire
    public function netAmount(): float
    {
        return round((float) $this->amount - (float) $this->fee_amount, 2);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
