<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'opened_at',
        'closed_at',
        'starting_cash',
        'cash_expected',
        'cash_actual',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'starting_cash' => 'integer',
        'cash_expected' => 'integer',
        'cash_actual' => 'integer',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** Human-friendly shift code, e.g. S-1042. */
    public function getCodeAttribute(): string
    {
        return 'S-'.str_pad((string) ($this->id + 1000), 4, '0', STR_PAD_LEFT);
    }

    /** Completed-sale revenue tied to this shift. */
    public function totalSales(): int
    {
        return (int) $this->sales()->where('status', 'completed')->sum('total');
    }

    /** Cash-only completed-sale revenue. */
    public function cashSales(): int
    {
        return (int) $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('total');
    }

    /** Drawer cash the system expects at close: starting float + cash sales. */
    public function expectedCash(): int
    {
        return $this->starting_cash + $this->cashSales();
    }
}
