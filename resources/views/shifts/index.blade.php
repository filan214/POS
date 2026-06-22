@php
    use Illuminate\Support\Carbon;

    $shift = \App\Support\MockData::currentShift();
    $history = \App\Support\MockData::shiftHistory();

    $mins = (int) $shift['opened_at']->diffInMinutes(now());
    $dur = app()->getLocale() === 'en'
        ? intdiv($mins, 60).'h '.($mins % 60).'m'
        : intdiv($mins, 60).'j '.($mins % 60).'m';
@endphp

<x-app-layout :title="__('shifts.title')" active="shifts">
    <div x-data="{
            closed: false,
            closeModal: false,
            expected: {{ $shift['expected_cash'] }},
            actual: '',
            starting: '',
            toast: null,
            money(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0); },
            get diff() { return (Number(this.actual) || 0) - this.expected; },
            confirmClose() { this.closed = true; this.closeModal = false; this.flash('{{ __('shifts.close_action') }} ✓'); },
            reopen() { this.closed = false; this.starting = ''; this.actual = ''; this.flash('{{ __('shifts.open_action') }} ✓'); },
            flash(m) { this.toast = m; clearTimeout(this._t); this._t = setTimeout(() => this.toast = null, 2200); }
         }">

        <x-page-header :title="__('shifts.title')" :subtitle="__('shifts.subtitle')" />

        {{-- ===== Current shift (open) ===== --}}
        <div x-show="!closed" class="mt-6 grid gap-5 lg:grid-cols-3">
            <div class="card lg:col-span-2">
                <div class="flex items-center justify-between border-b border-ink/[.06] px-6 py-4">
                    <div class="flex items-center gap-3">
                        <h2 class="font-bold text-ink-900">{{ __('shifts.current') }}</h2>
                        <x-badge variant="jade" dot>{{ __('common.status.open') }} · {{ $shift['code'] }}</x-badge>
                    </div>
                    <button @click="closeModal = true" class="btn-ink"><x-icon name="shifts" class="h-5 w-5" /> {{ __('shifts.close_action') }}</button>
                </div>
                <div class="grid grid-cols-2 gap-px bg-ink/[.05] sm:grid-cols-4">
                    @foreach ([
                        ['shifts.cashier', $shift['cashier'], false],
                        ['shifts.opened_at', $shift['opened_at']->format('H:i'), true],
                        ['shifts.duration', $dur, true],
                        ['shifts.starting_cash', rupiah($shift['starting_cash']), true],
                    ] as [$label, $value, $mono])
                        <div class="bg-white px-6 py-4">
                            <p class="text-xs font-medium text-ink-500">{{ __($label) }}</p>
                            <p class="mt-1 font-semibold text-ink-900 {{ $mono ? 'font-mono tabular' : '' }}">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- expected cash highlight --}}
            <div class="card flex flex-col justify-between bg-ink p-6 text-white">
                <div>
                    <p class="text-sm font-medium text-white/55">{{ __('shifts.expected_cash') }}</p>
                    <p class="mt-1 font-mono text-3xl font-extrabold tabular">{{ rupiah($shift['expected_cash']) }}</p>
                    <p class="mt-1 text-xs text-white/45">{{ __('shifts.starting_cash') }} + {{ __('shifts.cash_sales') }}</p>
                </div>
                <div class="mt-6 space-y-2.5 border-t border-white/10 pt-4 text-sm">
                    <div class="flex justify-between"><span class="text-white/55">{{ __('shifts.sales_count') }}</span><span class="font-mono font-semibold tabular">{{ $shift['sales_count'] }}</span></div>
                    <div class="flex justify-between"><span class="text-white/55">{{ __('shifts.cash_sales') }}</span><span class="font-mono font-semibold tabular">{{ rupiah($shift['cash_sales']) }}</span></div>
                    <div class="flex justify-between"><span class="text-white/55">{{ __('shifts.total_sales') }}</span><span class="font-mono font-semibold tabular">{{ rupiah($shift['total_sales']) }}</span></div>
                </div>
            </div>
        </div>

        {{-- ===== Open new shift (after close) ===== --}}
        <div x-show="closed" x-transition class="mt-6">
            <div class="card mx-auto max-w-lg p-8 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-paper"><x-icon name="shifts" class="h-7 w-7 text-ink-400" /></span>
                <h2 class="mt-4 text-lg font-bold text-ink-900">{{ __('shifts.none_title') }}</h2>
                <p class="mt-1 text-sm text-ink-500">{{ __('shifts.none_sub') }}</p>
                <div class="mx-auto mt-6 max-w-xs text-left">
                    <label class="label">{{ __('shifts.starting_cash') }}</label>
                    <input type="number" x-model="starting" placeholder="300000" class="input font-mono text-lg font-bold tabular">
                    <p class="mt-2 text-xs text-ink-400">{{ __('shifts.open_hint') }}</p>
                    <button @click="reopen()" class="btn-primary mt-4 w-full">{{ __('shifts.open_action') }}</button>
                </div>
            </div>
        </div>

        {{-- ===== History ===== --}}
        <div class="mt-8">
            <h2 class="text-lg font-bold tracking-tight text-ink-900">{{ __('shifts.history') }}</h2>
            <div class="card mt-3 overflow-hidden">
                <div class="overflow-x-auto scroll-slim">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-ink/[.06] bg-paper/60 text-xs uppercase tracking-wide text-ink-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">{{ __('shifts.col.shift') }}</th>
                                <th class="px-5 py-3 font-semibold">{{ __('shifts.col.cashier') }}</th>
                                <th class="px-5 py-3 font-semibold">{{ __('shifts.col.opened') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('shifts.col.sales') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('shifts.col.expected') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('shifts.col.actual') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('shifts.col.diff') }}</th>
                                <th class="px-5 py-3 font-semibold">{{ __('shifts.col.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/[.05]">
                            @foreach ($history as $h)
                                @php
                                    $d = $h['discrepancy'];
                                    [$variant, $word] = $d === 0 ? ['jade', __('shifts.balanced')] : ($d > 0 ? ['amber', __('shifts.over')] : ['chili', __('shifts.short')]);
                                @endphp
                                <tr class="hover:bg-paper/50">
                                    <td class="px-5 py-3 font-mono font-semibold text-ink-900">{{ $h['code'] }}</td>
                                    <td class="px-5 py-3 text-ink-700">{{ $h['cashier'] }}</td>
                                    <td class="px-5 py-3 text-ink-600">{{ locale_date($h['opened_at']) }}</td>
                                    <td class="px-5 py-3 text-right font-mono text-ink-700 tabular">{{ rupiah($h['total_sales']) }}</td>
                                    <td class="px-5 py-3 text-right font-mono text-ink-600 tabular">{{ rupiah($h['expected_cash']) }}</td>
                                    <td class="px-5 py-3 text-right font-mono font-semibold text-ink-900 tabular">{{ rupiah($h['actual_cash']) }}</td>
                                    <td class="px-5 py-3 text-right font-mono font-semibold tabular {{ $d < 0 ? 'text-chili-600' : ($d > 0 ? 'text-amber-700' : 'text-jade-700') }}">{{ $d > 0 ? '+' : '' }}{{ rupiah($d, false) }}</td>
                                    <td class="px-5 py-3"><x-badge :variant="$variant">{{ $word }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== Close shift modal ===== --}}
        <div x-show="closeModal" x-transition.opacity @keydown.escape.window="closeModal = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
            <div class="absolute inset-0 bg-ink-900/40" @click="closeModal = false"></div>
            <div x-show="closeModal" x-transition class="card relative w-full max-w-md p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-ink-900">{{ __('shifts.close_title') }}</h2>
                    <button @click="closeModal = false" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink/5"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <p class="mt-1 text-sm text-ink-500">{{ __('shifts.close_hint') }}</p>

                <div class="mt-5 flex items-center justify-between rounded-xl bg-paper px-4 py-3">
                    <span class="text-sm font-medium text-ink-600">{{ __('shifts.expected_preview') }}</span>
                    <span class="font-mono text-lg font-bold text-ink-900 tabular">{{ rupiah($shift['expected_cash']) }}</span>
                </div>

                <div class="mt-4">
                    <label class="label">{{ __('shifts.actual_cash') }}</label>
                    <input type="number" x-model="actual" placeholder="0" class="input font-mono text-lg font-bold tabular" autofocus>
                </div>

                <div x-show="actual !== ''" class="mt-4 flex items-center justify-between rounded-xl px-4 py-3"
                     :class="diff === 0 ? 'bg-jade-50' : (diff > 0 ? 'bg-amber-50' : 'bg-chili-50')">
                    <span class="text-sm font-semibold"
                          :class="diff === 0 ? 'text-jade-700' : (diff > 0 ? 'text-amber-700' : 'text-chili-700')"
                          x-text="diff === 0 ? '{{ __('shifts.balanced') }}' : (diff > 0 ? '{{ __('shifts.over') }}' : '{{ __('shifts.short') }}')"></span>
                    <span class="font-mono text-lg font-bold tabular"
                          :class="diff === 0 ? 'text-jade-700' : (diff > 0 ? 'text-amber-700' : 'text-chili-700')"
                          x-text="(diff > 0 ? '+' : '') + money(diff)"></span>
                </div>

                <div class="mt-6 flex gap-2">
                    <button @click="closeModal = false" class="btn-outline flex-1">{{ __('common.action.cancel') }}</button>
                    <button @click="confirmClose()" class="btn-ink flex-1">{{ __('shifts.close_action') }}</button>
                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div x-show="toast" x-transition.opacity class="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2" style="display:none">
            <div class="flex items-center gap-2 rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white shadow-lift">
                <x-icon name="check" class="h-4 w-4" /> <span x-text="toast"></span>
            </div>
        </div>
    </div>
</x-app-layout>
