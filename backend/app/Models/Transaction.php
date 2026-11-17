<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'trx_id',
        'payment_id',
        'type',
        'amount',
        'charge',
        'balance_after',
        'currency',
        'status',
        'description',
        'original_transaction_id',
        'refunded_amount',
        'bkash_response',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'charge'          => 'decimal:2',
        'balance_after'   => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'bkash_response'  => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Remaining amount that can still be refunded */
    public function getRefundableAmountAttribute(): float
    {
        return (float) $this->amount - (float) $this->refunded_amount;
    }

    public function isRefundable(): bool
    {
        return $this->status === 'completed'
            && $this->type === 'credit'
            && $this->refundable_amount > 0;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }
}
