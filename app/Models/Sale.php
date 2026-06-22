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
    ];

    protected $casts = [
        'total' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** Gross profit = revenue − snapshotted cost of goods. */
    public function profit(): int
    {
        return (int) $this->items->sum(fn (SaleItem $i) => $i->subtotal - ($i->cost_price_snapshot * $i->qty));
    }
}
