<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'status', 'billing_period', 'starts_at', 'ends_at',
        'amount', 'source', 'note', 'granted_by', 'plan_snapshot',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'amount' => 'decimal:2', 'plan_snapshot' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(SubscriptionPayment::class);
    }

    public function effectivePlan(): Plan
    {
        if ($this->plan_snapshot === null) {
            return $this->plan;
        }

        return new Plan(array_merge(['id' => $this->plan_id], $this->plan_snapshot));
    }
}
