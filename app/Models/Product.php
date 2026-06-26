<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'category',
        'cost_price',
        'sell_price',
        'stock_qty',
        'reorder_threshold',
        'image_path',
        'emoji',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'integer',
        'sell_price' => 'integer',
        'stock_qty' => 'integer',
        'reorder_threshold' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Computed fields the Blade/Alpine views expect alongside the columns.
     */
    protected $appends = ['margin', 'is_low', 'is_out'];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ---- Accessors ------------------------------------------------------

    public function getMarginAttribute(): int
    {
        return $this->sell_price > 0
            ? (int) round(($this->sell_price - $this->cost_price) / $this->sell_price * 100)
            : 0;
    }

    public function getIsLowAttribute(): bool
    {
        return $this->stock_qty > 0 && $this->stock_qty <= $this->reorder_threshold;
    }

    public function getIsOutAttribute(): bool
    {
        return $this->stock_qty <= 0;
    }

    // ---- Scopes ---------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_qty', '<=', 'reorder_threshold');
    }
}
