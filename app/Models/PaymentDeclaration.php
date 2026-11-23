<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentDeclaration extends Model
{
    protected $fillable = [
        'cycle_id', 'member_id', 'amount', 'payment_method', 'payer_phone', 'transaction_ref', 'note',
        'receipt_path', 'status', 'reviewed_at', 'review_note', 'contribution_id',
    ];

    public function hasReceipt(): bool
    {
        return filled($this->receipt_path);
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reviewed_at' => 'datetime'];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function contribution(): BelongsTo
    {
        return $this->belongsTo(Contribution::class);
    }

    public function methodLabel(): string
    {
        return ['moncash' => 'MonCash', 'natcash' => 'NatCash', 'bank' => 'Bank'][$this->payment_method] ?? $this->payment_method;
    }
}
