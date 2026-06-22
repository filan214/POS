<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportRangeTest extends TestCase
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
            'password' => Hash::make('password'), 'locale' => 'en',
        ]);
        $user->assignRole('owner');

        return $user;
    }

    /** Create a completed sale on a specific date (timestamps aren't fillable, so set created_at directly). */
    private function sale(User $cashier, Shift $shift, int $total, Carbon $at): void
    {
        $sale = Sale::create([
            'code' => 'TST-'.uniqid(),
            'shift_id' => $shift->id,
            'cashier_id' => $cashier->id,
            'total' => $total,
            'payment_method' => 'cash',
            'paid_amount' => $total,
            'change_amount' => 0,
            'status' => 'completed',
        ]);

        Sale::where('id', $sale->id)->update(['created_at' => $at]);
    }

    public function test_range_filter_changes_the_stats(): void
    {
        $owner = $this->owner();
        $shift = Shift::create([
            'cashier_id' => $owner->id, 'opened_at' => Carbon::now(),
            'starting_cash' => 0, 'status' => 'open',
        ]);

        $this->sale($owner, $shift, 10_000, Carbon::today()->setTime(10, 0));               // today
        $this->sale($owner, $shift, 25_000, Carbon::today()->subDays(10)->setTime(10, 0));  // 10 days ago

        $this->actingAs($owner);

        // Today: only the 10k sale is in range.
        $today = $this->get('/reports')->assertOk();
        $this->assertSame(10_000, $today->viewData('stats')['sales']);
        $this->assertSame(1, $today->viewData('stats')['transactions']);
        $this->assertSame('today', $today->viewData('range'));

        // Last 30 days: both sales are in range.
        $last30 = $this->get('/reports?range=last_30')->assertOk();
        $this->assertSame(35_000, $last30->viewData('stats')['sales']);
        $this->assertSame(2, $last30->viewData('stats')['transactions']);
        $this->assertSame('last_30', $last30->viewData('range'));
    }

    public function test_invalid_range_falls_back_to_today(): void
    {
        $this->actingAs($this->owner());

        $this->get('/reports?range=nonsense')
            ->assertOk()
            ->assertViewHas('range', 'today');
    }
}
