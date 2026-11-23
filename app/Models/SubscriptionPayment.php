<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'billing_period', 'amount', 'provider', 'reference_id', 'status',
        'payment_url', 'expires_at', 'completed_at', 'failure_reason', 'subscription_id',
        'plan_snapshot',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'expires_at' => 'datetime', 'completed_at' => 'datetime', 'plan_snapshot' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
