<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('owner', 'web');
        Role::findOrCreate('cashier', 'web');
    }

    private function owner(): User
    {
        $user = User::create([
            'name' => 'Owner One', 'email' => 'owner@test.dev',
            'password' => Hash::make('password'), 'locale' => 'id',
        ]);
        $user->assignRole('owner');

        return $user;
    }

    private function cashier(): User
    {
        $user = User::create([
            'name' => 'Cashier One', 'email' => 'cashier@test.dev',
            'password' => Hash::make('password'), 'locale' => 'id',
        ]);
        $user->assignRole('cashier');

        return $user;
    }

    private function product(int $stock = 50): Product
    {
        return Product::create([
            'sku' => 'SKU-1', 'barcode' => '111', 'name' => 'Test Item', 'category' => 'Makanan',
            'cost_price' => 1000, 'sell_price' => 2000, 'stock_qty' => $stock,
            'reorder_threshold' => 10, 'emoji' => '📦', 'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/pos')->assertRedirect('/login');
    }

    public function test_cashier_cannot_access_owner_screens(): void
    {
        $this->actingAs($this->cashier());

        $this->get('/products')->assertForbidden();
        $this->get('/reports')->assertForbidden();
    }

    public function test_owner_can_access_reports(): void
    {
        $this->actingAs($this->owner());

        $this->get('/reports')->assertOk();
    }

    public function test_completing_a_sale_decrements_stock_atomically(): void
    {
        $cashier = $this->cashier();
        $product = $this->product(50);

        Shift::create([
            'cashier_id' => $cashier->id, 'opened_at' => Carbon::now(),
            'starting_cash' => 100000, 'status' => 'open',
        ]);

        $this->actingAs($cashier)
            ->postJson('/pos/sale', [
                'payment_method' => 'cash',
                'paid_amount' => 10000,
                'items' => [['id' => $product->id, 'qty' => 3]],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(47, $product->fresh()->stock_qty);
        $this->assertSame(6000, Sale::first()->total);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'type' => 'sale', 'qty_change' => -3,
        ]);
    }

    public function test_sale_is_rejected_without_an_open_shift(): void
    {
        $cashier = $this->cashier();
        $product = $this->product(50);

        $this->actingAs($cashier)
            ->postJson('/pos/sale', [
                'payment_method' => 'cash', 'paid_amount' => 2000,
                'items' => [['id' => $product->id, 'qty' => 1]],
            ])
            ->assertStatus(422);

        $this->assertSame(50, $product->fresh()->stock_qty);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_sale_is_rejected_when_stock_is_insufficient(): void
    {
        $cashier = $this->cashier();
        $product = $this->product(2);

        Shift::create([
            'cashier_id' => $cashier->id, 'opened_at' => Carbon::now(),
            'starting_cash' => 100000, 'status' => 'open',
        ]);

        $this->actingAs($cashier)
            ->postJson('/pos/sale', [
                'payment_method' => 'cash', 'paid_amount' => 100000,
                'items' => [['id' => $product->id, 'qty' => 5]],
            ])
            ->assertStatus(422);

        $this->assertSame(2, $product->fresh()->stock_qty);
        $this->assertDatabaseCount('sales', 0);
    }
}
