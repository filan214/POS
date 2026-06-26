<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'shift_id',
        'cashier_id',
        'total',
        'payment_method',
        'paid_amount',
        'change_amount',
        'status',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'total' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
        'voided_at' => 'datetime',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /** The owner who voided this sale, if any. */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    /** Gross profit = revenue − snapshotted cost of goods. */
    public function profit(): int
    {
        return (int) $this->items->sum(fn (SaleItem $i) => $i->subtotal - ($i->cost_price_snapshot * $i->qty));
    }
}
