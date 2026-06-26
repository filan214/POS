<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /** Brand palette reused for the category doughnut. */
    private array $palette = ['#0E9F6E', '#16223A', '#E0A100', '#3D5A93', '#D8453A', '#0B7D57', '#6b7793'];

    public function dashboard(Request $request): View
    {
        $range = $this->normalizeRange($request->query('range'));

        return view('reports.dashboard', $this->gather($range));
    }

    /** PDF export of today's report (PRD §5.5) via dompdf. */
    public function exportPdf(): Response
    {
        $data = $this->gather('today');
        $data['generatedAt'] = Carbon::now();

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4');

        return $pdf->download('laporan-'.Carbon::today()->toDateString().'.pdf');
    }

    /** Allowed range keys; anything else falls back to 'today'. */
    private const RANGES = ['today', 'last_7', 'last_30', 'month'];

    private function normalizeRange(?string $range): string
    {
        return in_array($range, self::RANGES, true) ? $range : 'today';
    }

    /** Resolve a range key to its current and previous-period date windows. */
    private function window(string $range): array
    {
        $end = Carbon::today();

        $start = match ($range) {
            'last_7' => $end->copy()->subDays(6),
            'last_30' => $end->copy()->subDays(29),
            'month' => $end->copy()->startOfMonth(),
            default => $end->copy(), // today
        };

        // Previous period = the window of equal length immediately before this one.
        $days = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        return compact('start', 'end', 'prevStart', 'prevEnd');
    }

    /** All figures the dashboard and the PDF share, for the given range. */
    private function gather(string $range): array
    {
        $w = $this->window($range);

        return [
            'user' => auth()->user(),
            'range' => $range,
            'stats' => $this->stats($w['start'], $w['end'], $w['prevStart'], $w['prevEnd']),
            'trend' => $this->trend($range, $w['start'], $w['end']),
            'cats' => $this->categoryBreakdown($w['start'], $w['end']),
            'top' => $this->topProducts($w['start'], $w['end']),
            'low' => Product::active()->lowStock()->orderBy('stock_qty')->get(),
            'recon' => $this->reconciliation(),
            'recent' => $this->recentSales(),
        ];
    }

    private function stats(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd): array
    {
        $cur = $this->figuresBetween($start, $end);
        $prev = $this->figuresBetween($prevStart, $prevEnd);

        $basket = $cur['count'] > 0 ? (int) round($cur['sales'] / $cur['count']) : 0;
        $prevBasket = $prev['count'] > 0 ? $prev['sales'] / $prev['count'] : 0;

        return [
            'sales' => $cur['sales'],
            'sales_delta' => $this->delta($cur['sales'], $prev['sales']),
            'profit' => $cur['profit'],
            'profit_delta' => $this->delta($cur['profit'], $prev['profit']),
            'transactions' => $cur['count'],
            'transactions_delta' => $this->delta($cur['count'], $prev['count']),
            'basket' => $basket,
            'basket_delta' => $this->delta($basket, $prevBasket),
            'margin' => $cur['sales'] > 0 ? round($cur['profit'] / $cur['sales'] * 100, 1) : 0,
        ];
    }

    /** Sales total, profit and transaction count over an inclusive date range. */
    private function figuresBetween(Carbon $start, Carbon $end): array
    {
        $base = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        $sales = (int) (clone $base)->sum('total');
        $count = (clone $base)->count();

        $profit = (int) SaleItem::whereIn('sale_id', (clone $base)->select('id'))
            ->selectRaw('COALESCE(SUM(subtotal - cost_price_snapshot * qty), 0) AS p')
            ->value('p');

        return compact('sales', 'count', 'profit');
    }

    private function delta(float $now, float $prev): float
    {
        if ($prev <= 0) {
            return $now > 0 ? 100.0 : 0.0;
        }

        return round(($now - $prev) / $prev * 100, 1);
    }

    /** Revenue trend: hourly for a single day, otherwise daily across the range. */
    private function trend(string $range, Carbon $start, Carbon $end): array
    {
        $labels = [];
        $values = [];

        if ($range === 'today') {
            for ($h = 7; $h <= 22; $h++) {
                $labels[] = sprintf('%02d:00', $h);
                $values[] = $this->salesTotalBetween(
                    $start->copy()->setTime($h, 0),
                    $start->copy()->setTime($h, 59, 59),
                );
            }

            return compact('labels', 'values');
        }

        // For a week or less use weekday names; longer ranges use day + month.
        $useWeekday = ($start->diffInDays($end) + 1) <= 7;

        for ($day = $start->copy(); $day <= $end; $day->addDay()) {
            $labels[] = $useWeekday ? ucfirst($day->isoFormat('ddd')) : $day->isoFormat('D MMM');
            $values[] = $this->salesTotalBetween($day->copy()->startOfDay(), $day->copy()->endOfDay());
        }

        return compact('labels', 'values');
    }

    private function salesTotalBetween(Carbon $from, Carbon $to): int
    {
        return (int) Sale::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total');
    }

    /** Revenue share by category over the selected range. */
    private function categoryBreakdown(Carbon $start, Carbon $end): array
    {
        $rows = SaleItem::query()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('products.category')
            ->selectRaw('products.category AS label, SUM(sale_items.subtotal) AS revenue')
            ->orderByDesc('revenue')
            ->get();

        $total = max(1, (int) $rows->sum('revenue'));

        return $rows->values()->map(fn ($row, $i) => [
            'label' => $row->label,
            'value' => (int) round($row->revenue / $total * 100),
            'color' => $this->palette[$i % count($this->palette)],
        ])->all();
    }

    /** Best sellers by units sold over the selected range. */
    private function topProducts(Carbon $start, Carbon $end): Collection
    {
        return SaleItem::query()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('products.id', 'products.name', 'products.emoji')
            ->selectRaw('products.name AS name, products.emoji AS emoji, SUM(sale_items.qty) AS sold, SUM(sale_items.subtotal) AS revenue')
            ->orderByDesc('sold')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'emoji' => $row->emoji,
                'sold' => (int) $row->sold,
                'revenue' => (int) $row->revenue,
            ]);
    }

    /** Closed-shift cash reconciliation (owner sees all cashiers). */
    private function reconciliation(): Collection
    {
        return Shift::with('cashier')
            ->where('status', 'closed')
            ->latest('opened_at')
            ->limit(6)
            ->get()
            ->map(fn (Shift $s) => [
                'code' => $s->code,
                'cashier' => $s->cashier->name,
                'expected_cash' => $s->cash_expected ?? $s->expectedCash(),
                'actual_cash' => $s->cash_actual ?? 0,
                'discrepancy' => ($s->cash_actual ?? 0) - ($s->cash_expected ?? $s->expectedCash()),
            ]);
    }

    private function recentSales(): Collection
    {
        return Sale::withCount('items')
            ->where('status', 'completed')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Sale $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'at' => $s->created_at,
                'method' => $s->payment_method,
                'items' => $s->items_count,
                'total' => $s->total,
            ]);
    }
}
