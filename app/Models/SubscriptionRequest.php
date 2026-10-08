<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRequest extends Model
{
    public const METHODS = ['moncash' => 'MonCash', 'natcash' => 'NatCash', 'bank' => 'Bank'];

    protected $fillable = [
        'user_id', 'plan_id', 'billing_period', 'amount', 'payment_method', 'payer_phone',
        'transaction_ref', 'reference_code', 'note', 'status', 'reviewed_by', 'reviewed_at',
        'review_note', 'subscription_id',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Code à écrire dans la note du transfert pour identifier le paiement (stable entre l'affichage et l'envoi)
    public static function nextReferenceCode(User $user): string
    {
        return sprintf('LA-%04d-%03d', $user->id, static::where('user_id', $user->id)->count() + 1);
    }
}
