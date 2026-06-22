<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $open = $user->openShift();

        $historyQuery = Shift::with('cashier')
            ->where('status', 'closed')
            ->latest('opened_at');

        // Cashiers only see their own shift history (PRD §5.4).
        if (! $user->isOwner()) {
            $historyQuery->where('cashier_id', $user->id);
        }

        return view('shifts.index', [
            'shift' => $open ? $this->present($open) : null,
            'history' => $historyQuery->limit(20)->get()->map(fn (Shift $s) => [
                'code' => $s->code,
                'cashier' => $s->cashier->name,
                'opened_at' => $s->opened_at,
                'closed_at' => $s->closed_at,
                'starting_cash' => $s->starting_cash,
                'total_sales' => $s->totalSales(),
                'expected_cash' => $s->cash_expected ?? $s->expectedCash(),
                'actual_cash' => $s->cash_actual ?? 0,
                'discrepancy' => ($s->cash_actual ?? 0) - ($s->cash_expected ?? $s->expectedCash()),
            ]),
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starting_cash' => ['required', 'integer', 'min:0'],
        ]);

        if ($request->user()->openShift()) {
            return back()->with('status', __('shifts.flash.already_open'));
        }

        Shift::create([
            'cashier_id' => $request->user()->id,
            'opened_at' => Carbon::now(),
            'starting_cash' => $data['starting_cash'],
            'status' => 'open',
        ]);

        return back()->with('status', __('shifts.flash.opened'));
    }

    public function close(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cash_actual' => ['required', 'integer', 'min:0'],
        ]);

        $shift = $request->user()->openShift();

        if (! $shift) {
            return back()->with('status', __('shifts.flash.none_open'));
        }

        $expected = $shift->expectedCash();

        $shift->update([
            'closed_at' => Carbon::now(),
            'cash_expected' => $expected,
            'cash_actual' => $data['cash_actual'],
            'status' => 'closed',
        ]);

        return back()->with('status', __('shifts.flash.closed'));
    }

    /** Shape the open shift the way the Blade view consumes it. */
    private function present(Shift $shift): array
    {
        return [
            'code' => $shift->code,
            'cashier' => $shift->cashier->name,
            'opened_at' => $shift->opened_at,
            'status' => $shift->status,
            'starting_cash' => $shift->starting_cash,
            'cash_sales' => $shift->cashSales(),
            'total_sales' => $shift->totalSales(),
            'sales_count' => $shift->sales()->where('status', 'completed')->count(),
            'expected_cash' => $shift->expectedCash(),
        ];
    }
}
