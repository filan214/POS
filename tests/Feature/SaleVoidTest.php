<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleVoidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('owner', 'web');
        Role::findOrCreate('cashier', 'web');
    }

    private function user(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $role.'@test.dev',
            'password' => Hash::make('password'),
            'locale' => 'en',
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * A completed sale of $qty units. The product is left at $stockAfter — i.e.
     * already decremented by the sale, as it would be in production.
     *
     * @return array{0: Sale, 1: Product}
     */
    private function completedSale(User $cashier, int $qty = 3, int $stockAfter = 7): array
    {
        $product = Product::create([
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Test Item',
            'category' => 'Test',
            'cost_price' => 1000,
            'sell_price' => 2000,
            'stock_qty' => $stockAfter,
            'reorder_threshold' => 0,
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'cashier_id' => $cashier->id, 'opened_at' => Carbon::now(),
            'starting_cash' => 0, 'status' => 'open',
        ]);

        $sale = Sale::create([
            'code' => 'TRX-'.uniqid(),
            'shift_id' => $shift->id,
            'cashier_id' => $cashier->id,
            'total' => 2000 * $qty,
            'payment_method' => 'cash',
            'paid_amount' => 2000 * $qty,
            'change_amount' => 0,
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id,
            'qty' => $qty, 'unit_price' => 2000, 'cost_price_snapshot' => 1000,
            'subtotal' => 2000 * $qty,
        ]);

        return [$sale, $product];
    }

    public function test_owner_can_void_a_completed_sale_and_restock(): void
    {
        $owner = $this->user('owner');
        [$sale, $product] = $this->completedSale($this->user('cashier'), qty: 3, stockAfter: 7);

        $this->actingAs($owner)
            ->post(route('sales.void', $sale))
            ->assertRedirect()
            ->assertSessionHas('status');

        $sale->refresh();
        $this->assertSame('voided', $sale->status);
        $this->assertSame($owner->id, $sale->voided_by);
        $this->assertNotNull($sale->voided_at);

        // Stock restored 7 -> 10.
        $this->assertSame(10, $product->refresh()->stock_qty);

        // A reversing movement is logged, tied to the sale.
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'qty_change' => 3,
            'reference_id' => $sale->id,
            'note' => 'Void '.$sale->code,
        ]);
    }

    public function test_voided_sale_is_excluded_from_report_totals(): void
    {
        $owner = $this->user('owner');
        [$sale] = $this->completedSale($owner, qty: 2, stockAfter: 8); // 2 × 2000 = 4000 today

        $before = $this->actingAs($owner)->get('/reports')->viewData('stats')['sales'];
        $this->assertSame(4000, $before);

        $this->actingAs($owner)->post(route('sales.void', $sale));

        $after = $this->actingAs($owner)->get('/reports')->viewData('stats')['sales'];
        $this->assertSame(0, $after);
    }

    public function test_cashier_cannot_void_a_sale(): void
    {
        $cashier = $this->user('cashier');
        [$sale] = $this->completedSale($cashier);

        $this->actingAs($cashier)
            ->post(route('sales.void', $sale))
            ->assertForbidden();

        $this->assertSame('completed', $sale->refresh()->status);
    }

    public function test_a_sale_cannot_be_voided_twice(): void
    {
        $owner = $this->user('owner');
        [$sale, $product] = $this->completedSale($owner, qty: 3, stockAfter: 7);

        $this->actingAs($owner)->post(route('sales.void', $sale)); // 7 -> 10
        $this->assertSame(10, $product->refresh()->stock_qty);

        // Second attempt is a no-op with an error flash — stock not restored twice.
        $this->actingAs($owner)
            ->post(route('sales.void', $sale))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(10, $product->refresh()->stock_qty);
        $this->assertSame(1, StockMovement::where('reference_id', $sale->id)
            ->where('type', 'adjustment')->count());
    }
}
