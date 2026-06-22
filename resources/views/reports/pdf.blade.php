<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #16223A; font-size: 12px; margin: 0; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 13px; margin: 22px 0 6px; color: #1F3052; border-bottom: 1px solid #e3e1da; padding-bottom: 4px; }
        .muted { color: #6b7793; }
        .head { border-bottom: 2px solid #16223A; padding-bottom: 10px; margin-bottom: 6px; }
        .brand { color: #0E9F6E; font-weight: bold; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 8px; }
        thead th { background: #f5f4f0; color: #6b7793; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        tbody tr { border-bottom: 1px solid #efeee9; }
        .r { text-align: right; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .pos { color: #0E9F6E; } .neg { color: #D8453A; } .warn { color: #E0A100; }
        .cards td { border: 1px solid #e3e1da; width: 20%; vertical-align: top; }
        .cards .label { font-size: 10px; color: #6b7793; text-transform: uppercase; }
        .cards .val { font-size: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="head">
        <table>
            <tr>
                <td>
                    <h1>{{ __('reports.export_title') }}</h1>
                    <div class="muted">{{ __('reports.generated') }}: {{ locale_date($generatedAt) }} {{ $generatedAt->format('H:i') }}</div>
                </td>
                <td class="r">
                    <div class="brand">{{ __('pos.receipt.store') }}</div>
                    <div class="muted">{{ __('pos.receipt.address') }}</div>
                    <div class="muted">{{ __('pos.receipt.phone') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Summary --}}
    <h2>{{ __('reports.range.today') }}</h2>
    <table class="cards">
        <tr>
            <td><div class="label">{{ __('reports.stat.sales') }}</div><div class="val">{{ rupiah($stats['sales']) }}</div></td>
            <td><div class="label">{{ __('reports.stat.profit') }}</div><div class="val">{{ rupiah($stats['profit']) }}</div></td>
            <td><div class="label">{{ __('reports.stat.transactions') }}</div><div class="val">{{ $stats['transactions'] }}</div></td>
            <td><div class="label">{{ __('reports.stat.basket') }}</div><div class="val">{{ rupiah($stats['basket']) }}</div></td>
            <td><div class="label">{{ __('reports.stat.margin') }}</div><div class="val">{{ $stats['margin'] }}%</div></td>
        </tr>
    </table>

    {{-- Best sellers --}}
    <h2>{{ __('reports.top.title') }}</h2>
    <table>
        <thead><tr><th>#</th><th>{{ __('products.col.product') }}</th><th class="r">{{ __('reports.top.sold') }}</th><th class="r">{{ __('reports.recent.total') }}</th></tr></thead>
        <tbody>
            @foreach ($top as $i => $p)
                <tr>
                    <td class="mono">{{ $i + 1 }}</td>
                    <td>{{ $p['name'] }}</td>
                    <td class="r mono">{{ $p['sold'] }}</td>
                    <td class="r mono">{{ rupiah($p['revenue']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Low stock --}}
    <h2>{{ __('reports.low.title') }} ({{ $low->count() }})</h2>
    <table>
        <thead><tr><th>{{ __('products.col.product') }}</th><th>{{ __('products.col.category') }}</th><th class="r">{{ __('products.col.stock') }}</th><th class="r">{{ __('products.col.status') }}</th></tr></thead>
        <tbody>
            @forelse ($low as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->category }}</td>
                    <td class="r mono">{{ $p->stock_qty }}</td>
                    <td class="r {{ $p->stock_qty === 0 ? 'neg' : 'warn' }}">{{ $p->stock_qty === 0 ? __('common.status.out_of_stock') : __('common.status.low_stock') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">{{ __('reports.low.none') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Reconciliation --}}
    <h2>{{ __('reports.recon.title') }}</h2>
    <table>
        <thead><tr><th>{{ __('reports.recon.cashier') }}</th><th class="r">{{ __('reports.recon.expected') }}</th><th class="r">{{ __('reports.recon.actual') }}</th><th class="r">{{ __('reports.recon.diff') }}</th></tr></thead>
        <tbody>
            @foreach ($recon as $h)
                @php $d = $h['discrepancy']; @endphp
                <tr>
                    <td>{{ $h['cashier'] }} <span class="muted mono">{{ $h['code'] }}</span></td>
                    <td class="r mono">{{ rupiah($h['expected_cash']) }}</td>
                    <td class="r mono">{{ rupiah($h['actual_cash']) }}</td>
                    <td class="r mono {{ $d < 0 ? 'neg' : ($d > 0 ? 'warn' : 'pos') }}">{{ $d > 0 ? '+' : '' }}{{ rupiah($d, false) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Recent transactions --}}
    <h2>{{ __('reports.recent.title') }}</h2>
    <table>
        <thead><tr><th>{{ __('reports.recent.no') }}</th><th>{{ __('reports.recent.time') }}</th><th>{{ __('reports.recent.method') }}</th><th class="r">{{ __('reports.recent.items') }}</th><th class="r">{{ __('reports.recent.total') }}</th></tr></thead>
        <tbody>
            @foreach ($recent as $s)
                <tr>
                    <td class="mono">{{ $s['code'] }}</td>
                    <td>{{ $s['at']->format('H:i') }}</td>
                    <td>{{ __('common.payment.'.$s['method']) }}</td>
                    <td class="r mono">{{ $s['items'] }}</td>
                    <td class="r mono">{{ rupiah($s['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
