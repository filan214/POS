<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Static, in-memory sample data for the frontend-only phase.
 *
 * Every method mirrors the shape of the eventual Eloquent models (see PRD §8),
 * so swapping these out for real queries later is a drop-in change at the
 * controller/Livewire layer — the Blade views won't need to move.
 */
class MockData
{
    /** Catalogue rows — shape matches the `products` table. */
    public static function products(): Collection
    {
        return collect([
            ['sku' => 'MIE-001', 'barcode' => '8992388111014', 'name' => 'Indomie Goreng',            'category' => 'Makanan',   'cost_price' => 2800,  'sell_price' => 3500,  'stock_qty' => 124, 'reorder_threshold' => 24, 'emoji' => '🍜'],
            ['sku' => 'MIN-014', 'barcode' => '8886008101011', 'name' => 'Aqua 600ml',                'category' => 'Minuman',   'cost_price' => 2500,  'sell_price' => 4000,  'stock_qty' => 8,   'reorder_threshold' => 24, 'emoji' => '💧'],
            ['sku' => 'MIN-021', 'barcode' => '8992761111017', 'name' => 'Teh Botol Sosro 350ml',     'category' => 'Minuman',   'cost_price' => 3500,  'sell_price' => 5000,  'stock_qty' => 62,  'reorder_threshold' => 24, 'emoji' => '🍵'],
            ['sku' => 'MIN-009', 'barcode' => '8995899111022', 'name' => 'Kopi Kapal Api Sachet',     'category' => 'Minuman',   'cost_price' => 1200,  'sell_price' => 2000,  'stock_qty' => 210, 'reorder_threshold' => 40, 'emoji' => '☕'],
            ['sku' => 'SMB-002', 'barcode' => '8990011111028', 'name' => 'Beras Pandan Wangi 5kg',    'category' => 'Sembako',   'cost_price' => 62000, 'sell_price' => 72000, 'stock_qty' => 15,  'reorder_threshold' => 5,  'emoji' => '🍚'],
            ['sku' => 'SMB-005', 'barcode' => '8992222111035', 'name' => 'Minyak Goreng 1L',          'category' => 'Sembako',   'cost_price' => 16000, 'sell_price' => 19000, 'stock_qty' => 4,   'reorder_threshold' => 6,  'emoji' => '🛢️'],
            ['sku' => 'SMB-008', 'barcode' => '8993333111042', 'name' => 'Gula Pasir 1kg',            'category' => 'Sembako',   'cost_price' => 13000, 'sell_price' => 15500, 'stock_qty' => 33,  'reorder_threshold' => 8,  'emoji' => '🧂'],
            ['sku' => 'SMB-011', 'barcode' => '8994444111059', 'name' => 'Telur Ayam 1kg',            'category' => 'Sembako',   'cost_price' => 26000, 'sell_price' => 29000, 'stock_qty' => 21,  'reorder_threshold' => 6,  'emoji' => '🥚'],
            ['sku' => 'RKK-001', 'barcode' => '8995555111066', 'name' => 'Sampoerna Mild 16',         'category' => 'Rokok',     'cost_price' => 28000, 'sell_price' => 31000, 'stock_qty' => 40,  'reorder_threshold' => 10, 'emoji' => '🚬'],
            ['sku' => 'RKK-004', 'barcode' => '8996666111073', 'name' => 'Gudang Garam Surya 12',     'category' => 'Rokok',     'cost_price' => 22000, 'sell_price' => 25000, 'stock_qty' => 0,   'reorder_threshold' => 10, 'emoji' => '🚬'],
            ['sku' => 'MKN-007', 'barcode' => '8997777111080', 'name' => 'Chitato Sapi Panggang',     'category' => 'Makanan',   'cost_price' => 8000,  'sell_price' => 10500, 'stock_qty' => 37,  'reorder_threshold' => 12, 'emoji' => '🥔'],
            ['sku' => 'MKN-012', 'barcode' => '8998888111097', 'name' => 'SilverQueen Chunky',        'category' => 'Makanan',   'cost_price' => 9000,  'sell_price' => 12000, 'stock_qty' => 18,  'reorder_threshold' => 8,  'emoji' => '🍫'],
            ['sku' => 'PRW-003', 'barcode' => '8999999111103', 'name' => 'Sabun Lifebuoy',            'category' => 'Perawatan', 'cost_price' => 3000,  'sell_price' => 4500,  'stock_qty' => 52,  'reorder_threshold' => 12, 'emoji' => '🧼'],
            ['sku' => 'PRW-006', 'barcode' => '8990101111118', 'name' => 'Pepsodent 75g',             'category' => 'Perawatan', 'cost_price' => 7000,  'sell_price' => 9500,  'stock_qty' => 6,   'reorder_threshold' => 8,  'emoji' => '🪥'],
            ['sku' => 'PRW-010', 'barcode' => '8991201111125', 'name' => 'Sunsilk Sachet',            'category' => 'Perawatan', 'cost_price' => 700,   'sell_price' => 1500,  'stock_qty' => 305, 'reorder_threshold' => 50, 'emoji' => '🧴'],
            ['sku' => 'MIN-031', 'barcode' => '8992301111132', 'name' => 'Coca-Cola 390ml',           'category' => 'Minuman',   'cost_price' => 4000,  'sell_price' => 6000,  'stock_qty' => 48,  'reorder_threshold' => 24, 'emoji' => '🥤'],
            ['sku' => 'MKN-018', 'barcode' => '8993401111149', 'name' => 'Roti Tawar Sari Roti',      'category' => 'Makanan',   'cost_price' => 13000, 'sell_price' => 16000, 'stock_qty' => 10,  'reorder_threshold' => 6,  'emoji' => '🍞'],
            ['sku' => 'MIN-040', 'barcode' => '8994501111156', 'name' => 'Susu Ultra Coklat 250ml',   'category' => 'Minuman',   'cost_price' => 4500,  'sell_price' => 6500,  'stock_qty' => 9,   'reorder_threshold' => 12, 'emoji' => '🥛'],
            ['sku' => 'MIE-006', 'barcode' => '8995601111163', 'name' => 'Mie Sedaap Soto',           'category' => 'Makanan',   'cost_price' => 2700,  'sell_price' => 3400,  'stock_qty' => 96,  'reorder_threshold' => 24, 'emoji' => '🍲'],
            ['sku' => 'PRW-015', 'barcode' => '8996701111170', 'name' => 'Rinso Sachet',              'category' => 'Perawatan', 'cost_price' => 1500,  'sell_price' => 2500,  'stock_qty' => 80,  'reorder_threshold' => 20, 'emoji' => '🧺'],
        ])->map(function ($p, $i) {
            $p['id'] = $i + 1;
            $p['is_active'] = true;
            $p['margin'] = $p['sell_price'] > 0
                ? round(($p['sell_price'] - $p['cost_price']) / $p['sell_price'] * 100)
                : 0;
            $p['is_low'] = $p['stock_qty'] > 0 && $p['stock_qty'] <= $p['reorder_threshold'];
            $p['is_out'] = $p['stock_qty'] === 0;
            return $p;
        });
    }

    public static function categories(): Collection
    {
        return self::products()->pluck('category')->unique()->values();
    }

    public static function lowStock(): Collection
    {
        return self::products()
            ->filter(fn ($p) => $p['stock_qty'] <= $p['reorder_threshold'])
            ->sortBy('stock_qty')
            ->values();
    }

    /** Aggregate figures for the Products screen header. */
    public static function productStats(): array
    {
        $products = self::products();

        return [
            'total' => $products->count(),
            'low' => $products->where('is_low', true)->count(),
            'out' => $products->where('is_out', true)->count(),
            'value' => $products->sum(fn ($p) => $p['cost_price'] * $p['stock_qty']),
        ];
    }

    public static function stockMovements(): Collection
    {
        $now = Carbon::now();

        return collect([
            ['type' => 'sale',       'product' => 'Indomie Goreng',        'qty_change' => -3,  'note' => 'TRX-2041', 'at' => $now->copy()->subMinutes(6)],
            ['type' => 'sale',       'product' => 'Aqua 600ml',            'qty_change' => -2,  'note' => 'TRX-2040', 'at' => $now->copy()->subMinutes(18)],
            ['type' => 'restock',    'product' => 'Minyak Goreng 1L',      'qty_change' => 24,  'note' => 'PO-0312',  'at' => $now->copy()->subHours(2)],
            ['type' => 'adjustment', 'product' => 'Telur Ayam 1kg',        'qty_change' => -1,  'note' => 'Pecah',    'at' => $now->copy()->subHours(3)],
            ['type' => 'sale',       'product' => 'Teh Botol Sosro 350ml', 'qty_change' => -6,  'note' => 'TRX-2036', 'at' => $now->copy()->subHours(4)],
            ['type' => 'restock',    'product' => 'Beras Pandan Wangi 5kg','qty_change' => 10,  'note' => 'PO-0311',  'at' => $now->copy()->subHours(6)],
        ]);
    }

    // ---- People ---------------------------------------------------------

    /** The signed-in user. Role is driven by the demo login buttons. */
    public static function currentUser(): array
    {
        $role = session('role', 'owner');

        return $role === 'cashier'
            ? ['name' => 'Dewi Lestari', 'role' => 'cashier', 'initials' => 'DL']
            : ['name' => 'Filan Pratama', 'role' => 'owner', 'initials' => 'FP'];
    }

    // ---- Shifts ---------------------------------------------------------

    public static function currentShift(): array
    {
        $opened = Carbon::today()->setTime(8, 0);
        $cashSales = 1_212_000;
        $starting = 300_000;

        return [
            'code' => 'S-1042',
            'cashier' => 'Dewi Lestari',
            'opened_at' => $opened,
            'status' => 'open',
            'starting_cash' => $starting,
            'cash_sales' => $cashSales,
            'total_sales' => 1_850_000,
            'sales_count' => 37,
            'expected_cash' => $starting + $cashSales,
        ];
    }

    public static function shiftHistory(): Collection
    {
        return collect([
            ['code' => 'S-1041', 'cashier' => 'Dewi Lestari', 'opened_at' => Carbon::yesterday()->setTime(8, 0),  'closed_at' => Carbon::yesterday()->setTime(16, 5),  'starting_cash' => 300_000, 'total_sales' => 2_140_000, 'expected_cash' => 2_100_000, 'actual_cash' => 2_085_000],
            ['code' => 'S-1040', 'cashier' => 'Budi Santoso', 'opened_at' => Carbon::today()->subDays(2)->setTime(16, 0), 'closed_at' => Carbon::today()->subDays(2)->setTime(22, 10), 'starting_cash' => 300_000, 'total_sales' => 1_760_000, 'expected_cash' => 1_750_000, 'actual_cash' => 1_750_000],
            ['code' => 'S-1039', 'cashier' => 'Dewi Lestari', 'opened_at' => Carbon::today()->subDays(2)->setTime(8, 0),  'closed_at' => Carbon::today()->subDays(2)->setTime(16, 0),  'starting_cash' => 250_000, 'total_sales' => 1_980_000, 'expected_cash' => 1_950_000, 'actual_cash' => 1_970_000],
            ['code' => 'S-1038', 'cashier' => 'Budi Santoso', 'opened_at' => Carbon::today()->subDays(3)->setTime(16, 0), 'closed_at' => Carbon::today()->subDays(3)->setTime(22, 0),  'starting_cash' => 300_000, 'total_sales' => 2_310_000, 'expected_cash' => 2_300_000, 'actual_cash' => 2_290_000],
        ])->map(function ($s) {
            $s['discrepancy'] = $s['actual_cash'] - $s['expected_cash'];
            return $s;
        });
    }

    // ---- Sales ----------------------------------------------------------

    public static function recentSales(): Collection
    {
        $now = Carbon::now();

        return collect([
            ['code' => 'TRX-2041', 'at' => $now->copy()->subMinutes(6),  'method' => 'cash',  'items' => 4, 'total' => 38_500],
            ['code' => 'TRX-2040', 'at' => $now->copy()->subMinutes(18), 'method' => 'qris',  'items' => 2, 'total' => 12_000],
            ['code' => 'TRX-2039', 'at' => $now->copy()->subMinutes(33), 'method' => 'cash',  'items' => 6, 'total' => 74_000],
            ['code' => 'TRX-2038', 'at' => $now->copy()->subMinutes(51), 'method' => 'debit', 'items' => 1, 'total' => 72_000],
            ['code' => 'TRX-2037', 'at' => $now->copy()->subHours(1)->subMinutes(12), 'method' => 'cash', 'items' => 3, 'total' => 21_500],
            ['code' => 'TRX-2036', 'at' => $now->copy()->subHours(1)->subMinutes(40), 'method' => 'qris', 'items' => 5, 'total' => 46_000],
        ]);
    }

    // ---- Reports --------------------------------------------------------

    public static function dashboardStats(): array
    {
        return [
            'sales' => 1_850_000,
            'sales_delta' => 12.4,
            'profit' => 412_000,
            'profit_delta' => 8.1,
            'transactions' => 37,
            'transactions_delta' => 5.0,
            'basket' => 50_000,
            'basket_delta' => 3.2,
            'margin' => 22.3,
        ];
    }

    /** 7-day revenue series for the trend chart. */
    public static function salesTrend(): array
    {
        return [
            'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            'values' => [1_420_000, 1_680_000, 1_540_000, 1_910_000, 2_240_000, 2_680_000, 1_850_000],
        ];
    }

    public static function categoryBreakdown(): array
    {
        return [
            ['label' => 'Sembako',   'value' => 38, 'color' => '#0E9F6E'],
            ['label' => 'Minuman',   'value' => 27, 'color' => '#16223A'],
            ['label' => 'Makanan',   'value' => 18, 'color' => '#E0A100'],
            ['label' => 'Rokok',     'value' => 11, 'color' => '#3D5A93'],
            ['label' => 'Perawatan', 'value' => 6,  'color' => '#D8453A'],
        ];
    }

    public static function topProducts(): Collection
    {
        return collect([
            ['name' => 'Indomie Goreng',       'emoji' => '🍜', 'sold' => 86, 'revenue' => 301_000],
            ['name' => 'Aqua 600ml',           'emoji' => '💧', 'sold' => 64, 'revenue' => 256_000],
            ['name' => 'Kopi Kapal Api Sachet','emoji' => '☕', 'sold' => 58, 'revenue' => 116_000],
            ['name' => 'Teh Botol Sosro 350ml','emoji' => '🍵', 'sold' => 47, 'revenue' => 235_000],
            ['name' => 'Sampoerna Mild 16',    'emoji' => '🚬', 'sold' => 29, 'revenue' => 899_000],
        ]);
    }
}
