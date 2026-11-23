<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Payout extends Model
{
    protected $fillable = [
        'cycle_id', 'member_id', 'amount', 'fee_percent', 'fee_amount', 'paid_at', 'confirmed',
        'method', 'account', 'account_name', 'member_response', 'member_responded_at', 'member_note',
        'sender_method', 'sender_phone', 'transaction_ref', 'note', 'receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',       // total collecté pour le tour (brut)
            'fee_percent' => 'decimal:2',
            'fee_amount' => 'decimal:2',   // commission retenue par l'organisateur
            'paid_at' => 'date',
            'confirmed' => 'boolean',
            'member_responded_at' => 'datetime',
        ];
    }

    // Montant réellement remis au bénéficiaire
    public function netAmount(): float
    {
        return round((float) $this->amount - (float) $this->fee_amount, 2);
    }

    public function isDisputed(): bool
    {
        return $this->member_response === 'not_received';
    }

    public function isReceived(): bool
    {
        return $this->member_response === 'received';
    }

    public function hasReceipt(): bool
    {
        return filled($this->receipt_path);
    }

    public function receiptExists(): bool
    {
        return $this->hasReceipt() && Storage::disk('local')->exists($this->receipt_path);
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
