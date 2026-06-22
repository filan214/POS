<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    /** Brand palette reused for the category doughnut. */
    private array $palette = ['#0E9F6E', '#16223A', '#E0A100', '#3D5A93', '#D8453A', '#0B7D57', '#6b7793'];

    public function dashboard(): View
    {
        return view('reports.dashboard', $this->gather());
    }

    /** PDF export of today's report (PRD §5.5) via dompdf. */
    public function exportPdf(): Response
    {
        $data = $this->gather();
        $data['generatedAt'] = Carbon::now();

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4');

        return $pdf->download('laporan-'.Carbon::today()->toDateString().'.pdf');
    }

    /** All figures the dashboard and the PDF share. */
    private function gather(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        return [
            'user' => auth()->user(),
            'stats' => $this->stats($today, $yesterday),
            'trend' => $this->trend(),
            'cats' => $this->categoryBreakdown(),
            'top' => $this->topProducts(),
            'low' => Product::active()->lowStock()->orderBy('stock_qty')->get(),
            'recon' => $this->reconciliation(),
            'recent' => $this->recentSales(),
        ];
    }

    private function stats(Carbon $today, Carbon $yesterday): array
    {
        $todayFig = $this->dayFigures($today);
        $prevFig = $this->dayFigures($yesterday);

        $basket = $todayFig['count'] > 0 ? (int) round($todayFig['sales'] / $todayFig['count']) : 0;
        $prevBasket = $prevFig['count'] > 0 ? $prevFig['sales'] / $prevFig['count'] : 0;

        return [
            'sales' => $todayFig['sales'],
            'sales_delta' => $this->delta($todayFig['sales'], $prevFig['sales']),
            'profit' => $todayFig['profit'],
            'profit_delta' => $this->delta($todayFig['profit'], $prevFig['profit']),
            'transactions' => $todayFig['count'],
            'transactions_delta' => $this->delta($todayFig['count'], $prevFig['count']),
            'basket' => $basket,
            'basket_delta' => $this->delta($basket, $prevBasket),
            'margin' => $todayFig['sales'] > 0 ? round($todayFig['profit'] / $todayFig['sales'] * 100, 1) : 0,
        ];
    }

    /** Sales total, profit and transaction count for a single day. */
    private function dayFigures(Carbon $day): array
    {
        $base = Sale::where('status', 'completed')->whereDate('created_at', $day->toDateString());

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

    /** Last 7 days of revenue with locale-aware weekday labels. */
    private function trend(): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = ucfirst($day->isoFormat('ddd'));
            $values[] = (int) Sale::where('status', 'completed')
                ->whereDate('created_at', $day->toDateString())
                ->sum('total');
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** Revenue share by category over the last 7 days. */
    private function categoryBreakdown(): array
    {
        $since = Carbon::today()->subDays(6)->startOfDay();

        $rows = SaleItem::query()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $since)
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

    /** Best sellers by units sold over the last 7 days. */
    private function topProducts(): \Illuminate\Support\Collection
    {
        $since = Carbon::today()->subDays(6)->startOfDay();

        return SaleItem::query()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $since)
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
    private function reconciliation(): \Illuminate\Support\Collection
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

    private function recentSales(): \Illuminate\Support\Collection
    {
        return Sale::withCount('items')
            ->where('status', 'completed')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Sale $s) => [
                'code' => $s->code,
                'at' => $s->created_at,
                'method' => $s->payment_method,
                'items' => $s->items_count,
                'total' => $s->total,
            ]);
    }
}
