<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cycle extends Model
{
   protected $fillable = [
    'tontine_id', 'round', 'cycle_number', 'beneficiary_member_id', 'deadline', 'status',
];

    protected function casts(): array
    {
        return ['deadline' => 'date'];
    }

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'beneficiary_member_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class);
    }

   public function isLate(): bool
{
    // En retard seulement à partir du lendemain de la date limite
    return $this->status === 'open' && $this->deadline->lt(today());
}

    public function paymentProgress(): array
    {
        $total = $this->tontine->activeMembersCount();
        $paid = $this->contributions()->count(); // unique(cycle_id, member_id)

        return [
            'paid' => $paid,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($paid / $total * 100) : 0,
        ];
    }
}