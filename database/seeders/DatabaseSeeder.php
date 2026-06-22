<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\MockData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    private int $trxCounter = 2000;

    public function run(): void
    {
        $this->seedRoles();

        $owner = User::create([
            'name' => 'Filan Pratama',
            'email' => 'owner@warungtanti.test',
            'password' => Hash::make('password'),
            'locale' => 'id',
        ]);
        $owner->assignRole('owner');

        $dewi = User::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@warungtanti.test',
            'password' => Hash::make('password'),
            'locale' => 'id',
        ]);
        $dewi->assignRole('cashier');

        $budi = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@warungtanti.test',
            'password' => Hash::make('password'),
            'locale' => 'id',
        ]);
        $budi->assignRole('cashier');

        // ---- Products (reuse the curated catalogue) ----------------------
        foreach (MockData::products() as $p) {
            Product::create([
                'sku' => $p['sku'],
                'barcode' => $p['barcode'],
                'name' => $p['name'],
                'category' => $p['category'],
                'cost_price' => $p['cost_price'],
                'sell_price' => $p['sell_price'],
                'stock_qty' => $p['stock_qty'],
                'reorder_threshold' => $p['reorder_threshold'],
                'emoji' => $p['emoji'],
                'is_active' => true,
            ]);
        }

        $products = Product::all();
        $cashiers = [$dewi, $budi];

        // ---- Closed shift history + their sales (6 days → yesterday) -----
        for ($daysAgo = 6; $daysAgo >= 1; $daysAgo--) {
            $cashier = $cashiers[$daysAgo % 2];
            $day = Carbon::today()->subDays($daysAgo);

            $shift = Shift::create([
                'cashier_id' => $cashier->id,
                'opened_at' => $day->copy()->setTime(8, 0),
                'closed_at' => $day->copy()->setTime(16, 5),
                'starting_cash' => 300_000,
                'status' => 'closed',
            ]);

            $this->seedSales($shift, $cashier, $products, random_int(20, 34), $day);

            $expected = $shift->expectedCash();
            $discrepancy = [-15_000, -5_000, 0, 0, 5_000][array_rand([-15_000, -5_000, 0, 0, 5_000])];
            $shift->update([
                'cash_expected' => $expected,
                'cash_actual' => $expected + $discrepancy,
            ]);
        }

        // ---- Today's OPEN shift (Dewi) ----------------------------------
        $openShift = Shift::create([
            'cashier_id' => $dewi->id,
            'opened_at' => Carbon::today()->setTime(8, 0),
            'starting_cash' => 300_000,
            'status' => 'open',
        ]);

        $this->seedSales($openShift, $dewi, $products, 37, Carbon::today());

        // ---- A few restock / adjustment movements for log variety -------
        $byName = $products->keyBy('name');
        $manual = [
            ['Minyak Goreng 1L', 'restock', 24, 'PO-0312', Carbon::now()->subHours(2)],
            ['Beras Pandan Wangi 5kg', 'restock', 10, 'PO-0311', Carbon::now()->subHours(6)],
            ['Telur Ayam 1kg', 'adjustment', -1, 'Pecah', Carbon::now()->subHours(3)],
        ];
        foreach ($manual as [$name, $type, $change, $note, $at]) {
            if ($product = $byName->get($name)) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => $type,
                    'qty_change' => $change,
                    'note' => $note,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
        }
    }

    /**
     * Create the spatie roles and permissions (PRD §5.3) and grant them.
     * Owner gets everything; cashier gets the operational subset.
     */
    private function seedRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'process-sales',
            'manage-shifts',
            'manage-products',
            'view-reports',
            'manage-users',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $owner = Role::findOrCreate('owner', 'web');
        $owner->syncPermissions($permissions);

        $cashier = Role::findOrCreate('cashier', 'web');
        $cashier->syncPermissions(['process-sales', 'manage-shifts']);
    }

    /**
     * Generate $count completed sales for a shift, with line items and a
     * matching `sale` stock movement per item. Seeded stock levels are treated
     * as already-current, so historical movements are illustrative only.
     */
    private function seedSales(Shift $shift, User $cashier, $products, int $count, Carbon $day): void
    {
        $methods = array_merge(
            array_fill(0, 11, 'cash'),
            array_fill(0, 6, 'qris'),
            array_fill(0, 3, 'debit'),
        );

        $isToday = $day->isToday();
        $endMinutes = $isToday
            ? max(1, (int) Carbon::today()->setTime(8, 0)->diffInMinutes(now()))
            : 8 * 60; // 08:00 → 16:00 window for past days

        for ($i = 0; $i < $count; $i++) {
            $at = $day->copy()->setTime(8, 0)->addMinutes(random_int(1, $endMinutes));
            $lineCount = random_int(1, 6);
            $picks = $products->random(min($lineCount, $products->count()));

            $total = 0;
            $lines = [];
            foreach ($picks as $product) {
                $qty = random_int(1, 3);
                $subtotal = $product->sell_price * $qty;
                $total += $subtotal;
                $lines[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                ];
            }

            $method = $methods[array_rand($methods)];
            $paid = $method === 'cash'
                ? (int) (ceil($total / 5000) * 5000)
                : $total;

            $sale = Sale::create([
                'code' => 'TRX-'.(++$this->trxCounter),
                'shift_id' => $shift->id,
                'cashier_id' => $cashier->id,
                'total' => $total,
                'payment_method' => $method,
                'paid_amount' => $paid,
                'change_amount' => $paid - $total,
                'status' => 'completed',
                'created_at' => $at,
                'updated_at' => $at,
            ]);

            foreach ($lines as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'unit_price' => $line['product']->sell_price,
                    'cost_price_snapshot' => $line['product']->cost_price,
                    'subtotal' => $line['subtotal'],
                ]);

                StockMovement::create([
                    'product_id' => $line['product']->id,
                    'type' => 'sale',
                    'qty_change' => -$line['qty'],
                    'reference_id' => $sale->id,
                    'note' => $sale->code,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
        }
    }
}
