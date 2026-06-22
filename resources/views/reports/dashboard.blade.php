@php
    $user = \App\Support\MockData::currentUser();
    $stats = \App\Support\MockData::dashboardStats();
    $trend = \App\Support\MockData::salesTrend();
    $cats = \App\Support\MockData::categoryBreakdown();
    $top = \App\Support\MockData::topProducts();
    $low = \App\Support\MockData::lowStock();
    $recon = \App\Support\MockData::shiftHistory();
    $recent = \App\Support\MockData::recentSales();
@endphp

<x-app-layout :title="__('reports.title')" active="reports">
    <div x-data="{ range: 'today' }">
        <x-page-header :title="__('reports.title')" :subtitle="__('reports.greeting', ['name' => $user['name']])">
            <x-slot:actions>
                <div class="inline-flex items-center gap-0.5 rounded-xl border border-ink/10 bg-white p-0.5">
                    @foreach (['today' => __('reports.range.today'), 'last_7' => __('reports.range.last_7'), 'last_30' => __('reports.range.last_30'), 'month' => __('reports.range.month')] as $key => $label)
                        <button @click="range = '{{ $key }}'"
                                class="rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors"
                                :class="range === '{{ $key }}' ? 'bg-ink text-white' : 'text-ink-600 hover:text-ink-900'">{{ $label }}</button>
                    @endforeach
                </div>
                <button onclick="window.print()" class="btn-outline"><x-icon name="printer" class="h-5 w-5" /> {{ __('common.action.export_pdf') }}</button>
            </x-slot:actions>
        </x-page-header>

        {{-- Stat cards --}}
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
            <x-stat-card :label="__('reports.stat.sales')" :value="rupiah($stats['sales'])" :delta="$stats['sales_delta']" :delta-label="__('reports.stat.vs_prev')" icon="banknotes" tone="jade" />
            <x-stat-card :label="__('reports.stat.profit')" :value="rupiah($stats['profit'])" :delta="$stats['profit_delta']" :delta-label="__('reports.stat.vs_prev')" icon="sparkles" tone="amber" />
            <x-stat-card :label="__('reports.stat.transactions')" :value="$stats['transactions']" :delta="$stats['transactions_delta']" :delta-label="__('reports.stat.vs_prev')" icon="receipt" tone="ink" />
            <x-stat-card :label="__('reports.stat.basket')" :value="rupiah($stats['basket'])" :delta="$stats['basket_delta']" :delta-label="__('reports.stat.vs_prev')" icon="pos" tone="ink" />
            <x-stat-card :label="__('reports.stat.margin')" :value="$stats['margin'].'%'" icon="scale" tone="jade" />
        </div>

        {{-- Charts --}}
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <div class="card p-5 lg:col-span-2">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-bold text-ink-900">{{ __('reports.chart.trend') }}</h2>
                        <p class="text-sm text-ink-500">{{ __('reports.chart.trend_sub') }}</p>
                    </div>
                </div>
                <div class="mt-4 h-64"><canvas id="trendChart"></canvas></div>
            </div>

            <div class="card p-5">
                <h2 class="font-bold text-ink-900">{{ __('reports.chart.category') }}</h2>
                <p class="text-sm text-ink-500">{{ __('reports.chart.category_sub') }}</p>
                <div class="relative mx-auto mt-4 h-44 w-44"><canvas id="categoryChart"></canvas></div>
                <ul class="mt-5 space-y-2">
                    @foreach ($cats as $c)
                        <li class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-ink-700">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $c['color'] }}"></span>
                                {{ $c['label'] }}
                            </span>
                            <span class="font-mono font-semibold text-ink-900 tabular">{{ $c['value'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Best sellers + low stock --}}
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <div class="card p-5 lg:col-span-2">
                <h2 class="font-bold text-ink-900">{{ __('reports.top.title') }}</h2>
                <ul class="mt-4 space-y-1">
                    @foreach ($top as $i => $p)
                        <li class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-paper/60">
                            <span class="w-5 text-center font-mono text-sm font-bold text-ink-400 tabular">{{ $i + 1 }}</span>
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-paper text-xl">{{ $p['emoji'] }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-ink-900">{{ $p['name'] }}</p>
                                <p class="text-xs text-ink-500">{{ $p['sold'] }} {{ __('reports.top.sold') }}</p>
                            </div>
                            <span class="font-mono font-bold text-jade-700 tabular">{{ rupiah($p['revenue']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-ink-900">{{ __('reports.low.title') }}</h2>
                    <x-badge variant="chili">{{ $low->count() }}</x-badge>
                </div>
                <p class="text-sm text-ink-500">{{ __('reports.low.sub') }}</p>
                @if ($low->isEmpty())
                    <p class="mt-6 text-sm text-ink-500">{{ __('reports.low.none') }}</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($low as $p)
                            <li class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-paper text-lg">{{ $p['emoji'] }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink-900">{{ $p['name'] }}</p>
                                    <p class="text-xs text-ink-400">{{ __('products.reorder_at', ['n' => $p['reorder_threshold']]) }}</p>
                                </div>
                                <span class="rounded-full px-2 py-1 text-xs font-bold tabular {{ $p['stock_qty'] === 0 ? 'bg-chili-50 text-chili-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $p['stock_qty'] === 0 ? __('common.status.out_of_stock') : __('reports.low.left', ['n' => $p['stock_qty']]) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Reconciliation + recent --}}
        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <div class="card overflow-hidden">
                <div class="border-b border-ink/[.06] px-5 py-4">
                    <h2 class="font-bold text-ink-900">{{ __('reports.recon.title') }}</h2>
                    <p class="text-sm text-ink-500">{{ __('reports.recon.sub') }}</p>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-paper/60 text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th class="px-5 py-2.5 font-semibold">{{ __('reports.recon.cashier') }}</th>
                            <th class="px-5 py-2.5 text-right font-semibold">{{ __('reports.recon.expected') }}</th>
                            <th class="px-5 py-2.5 text-right font-semibold">{{ __('reports.recon.actual') }}</th>
                            <th class="px-5 py-2.5 text-right font-semibold">{{ __('reports.recon.diff') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/[.05]">
                        @foreach ($recon as $h)
                            @php $d = $h['discrepancy']; @endphp
                            <tr>
                                <td class="px-5 py-2.5">
                                    <p class="font-medium text-ink-800">{{ $h['cashier'] }}</p>
                                    <p class="font-mono text-xs text-ink-400">{{ $h['code'] }}</p>
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono text-ink-600 tabular">{{ rupiah($h['expected_cash']) }}</td>
                                <td class="px-5 py-2.5 text-right font-mono text-ink-900 tabular">{{ rupiah($h['actual_cash']) }}</td>
                                <td class="px-5 py-2.5 text-right font-mono font-semibold tabular {{ $d < 0 ? 'text-chili-600' : ($d > 0 ? 'text-amber-700' : 'text-jade-700') }}">{{ $d > 0 ? '+' : '' }}{{ rupiah($d, false) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-ink/[.06] px-5 py-4">
                    <h2 class="font-bold text-ink-900">{{ __('reports.recent.title') }}</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-paper/60 text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th class="px-5 py-2.5 font-semibold">{{ __('reports.recent.no') }}</th>
                            <th class="px-5 py-2.5 font-semibold">{{ __('reports.recent.time') }}</th>
                            <th class="px-5 py-2.5 font-semibold">{{ __('reports.recent.method') }}</th>
                            <th class="px-5 py-2.5 text-right font-semibold">{{ __('reports.recent.items') }}</th>
                            <th class="px-5 py-2.5 text-right font-semibold">{{ __('reports.recent.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/[.05]">
                        @foreach ($recent as $s)
                            @php
                                $mv = ['cash' => 'jade', 'qris' => 'ink', 'debit' => 'amber'][$s['method']];
                            @endphp
                            <tr>
                                <td class="px-5 py-2.5 font-mono text-xs font-semibold text-ink-900">{{ $s['code'] }}</td>
                                <td class="px-5 py-2.5 text-ink-600">{{ $s['at']->format('H:i') }}</td>
                                <td class="px-5 py-2.5"><x-badge :variant="$mv">{{ __('common.payment.'.$s['method']) }}</x-badge></td>
                                <td class="px-5 py-2.5 text-right font-mono text-ink-600 tabular">{{ $s['items'] }}</td>
                                <td class="px-5 py-2.5 text-right font-mono font-semibold text-ink-900 tabular">{{ rupiah($s['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('head')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
                integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4"
                crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (!window.Chart) return;

                const idr = (v) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
                const font = "'Plus Jakarta Sans', sans-serif";
                Chart.defaults.font.family = font;
                Chart.defaults.color = '#6b7793';

                // --- Sales trend (line) ---
                const trend = @js($trend);
                const tctx = document.getElementById('trendChart');
                if (tctx) {
                    const g = tctx.getContext('2d').createLinearGradient(0, 0, 0, 256);
                    g.addColorStop(0, 'rgba(14, 159, 110, 0.20)');
                    g.addColorStop(1, 'rgba(14, 159, 110, 0)');
                    new Chart(tctx, {
                        type: 'line',
                        data: {
                            labels: trend.labels,
                            datasets: [{
                                data: trend.values,
                                borderColor: '#0E9F6E',
                                backgroundColor: g,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#0E9F6E',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#16223A', padding: 12, cornerRadius: 10,
                                    callbacks: { label: (c) => idr(c.parsed.y) },
                                },
                            },
                            scales: {
                                y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(22,34,58,0.06)' }, ticks: { callback: (v) => 'Rp ' + (v / 1000) + 'k' } },
                                x: { border: { display: false }, grid: { display: false } },
                            },
                        },
                    });
                }

                // --- Category (doughnut) ---
                const cats = @js($cats);
                const cctx = document.getElementById('categoryChart');
                if (cctx) {
                    new Chart(cctx, {
                        type: 'doughnut',
                        data: {
                            labels: cats.map((c) => c.label),
                            datasets: [{ data: cats.map((c) => c.value), backgroundColor: cats.map((c) => c.color), borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '68%',
                            plugins: {
                                legend: { display: false },
                                tooltip: { backgroundColor: '#16223A', padding: 10, cornerRadius: 10, callbacks: { label: (c) => c.label + ': ' + c.parsed + '%' } },
                            },
                        },
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
