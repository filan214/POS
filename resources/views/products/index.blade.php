@php
    $products = \App\Support\MockData::products()->values();
    $stats = \App\Support\MockData::productStats();
    $movements = \App\Support\MockData::stockMovements();
@endphp

<x-app-layout :title="__('products.title')" active="products">
    <div x-data="{
            products: {{ Js::from($products) }},
            search: '',
            category: 'all',
            lowOnly: false,
            panel: false,
            editing: null,
            imagePreview: null,
            toast: null,
            money(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0); },
            get categories() { return ['all', ...new Set(this.products.map(p => p.category))]; },
            get filtered() {
                const q = this.search.trim().toLowerCase();
                return this.products.filter(p =>
                    (this.category === 'all' || p.category === this.category) &&
                    (!this.lowOnly || p.stock_qty <= p.reorder_threshold) &&
                    (!q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || String(p.barcode).includes(q)));
            },
            openNew() { this.editing = null; this.imagePreview = null; this.panel = true; },
            openEdit(p) { this.editing = p; this.imagePreview = null; this.panel = true; },
            preview(e) { const f = e.target.files[0]; if (f) this.imagePreview = URL.createObjectURL(f); },
            save() { this.panel = false; this.flash('{{ __('common.action.save') }} ✓'); },
            flash(m) { this.toast = m; clearTimeout(this._t); this._t = setTimeout(() => this.toast = null, 2000); }
         }">

        <x-page-header :title="__('products.title')" :subtitle="__('products.subtitle')">
            <x-slot:actions>
                <button @click="openNew()" class="btn-primary"><x-icon name="plus" class="h-5 w-5" /> {{ __('products.add') }}</button>
            </x-slot:actions>
        </x-page-header>

        {{-- Stats --}}
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat-card :label="__('products.stat.total')" :value="$stats['total']" icon="cube" tone="ink" />
            <x-stat-card :label="__('products.stat.low')" :value="$stats['low']" icon="alert" tone="amber" />
            <x-stat-card :label="__('products.stat.out')" :value="$stats['out']" icon="x" tone="chili" />
            <x-stat-card :label="__('products.stat.value')" :value="rupiah($stats['value'])" icon="banknotes" tone="jade" />
        </div>

        {{-- Toolbar --}}
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
                <input type="text" x-model="search" placeholder="{{ __('products.search_placeholder') }}" class="input pl-11">
            </label>
            <select x-model="category" class="input sm:w-48">
                <template x-for="c in categories" :key="c">
                    <option :value="c" x-text="c === 'all' ? '{{ __('products.all_categories') }}' : c"></option>
                </template>
            </select>
            <label class="inline-flex cursor-pointer items-center gap-2.5 rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-semibold text-ink-700">
                <input type="checkbox" x-model="lowOnly" class="h-4 w-4 rounded border-ink/25 text-jade focus:ring-jade">
                {{ __('products.low_only') }}
            </label>
        </div>

        {{-- Table --}}
        <div class="card mt-4 overflow-hidden">
            <div class="overflow-x-auto scroll-slim">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-ink/[.06] bg-paper/60 text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">{{ __('products.col.product') }}</th>
                            <th class="px-5 py-3 font-semibold">{{ __('products.col.category') }}</th>
                            <th class="px-5 py-3 text-right font-semibold">{{ __('products.col.cost') }}</th>
                            <th class="px-5 py-3 text-right font-semibold">{{ __('products.col.price') }}</th>
                            <th class="px-5 py-3 text-right font-semibold">{{ __('products.col.margin') }}</th>
                            <th class="px-5 py-3 text-right font-semibold">{{ __('products.col.stock') }}</th>
                            <th class="px-5 py-3 font-semibold">{{ __('products.col.status') }}</th>
                            <th class="px-5 py-3 text-right font-semibold">{{ __('products.col.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/[.05]">
                        <template x-for="p in filtered" :key="p.id">
                            <tr class="hover:bg-paper/50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-paper text-xl" x-text="p.emoji"></span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink-900" x-text="p.name"></p>
                                            <p class="font-mono text-xs text-ink-400" x-text="p.sku + ' · ' + p.barcode"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3"><span class="badge-ink" x-text="p.category"></span></td>
                                <td class="px-5 py-3 text-right font-mono text-ink-600 tabular" x-text="money(p.cost_price)"></td>
                                <td class="px-5 py-3 text-right font-mono font-semibold text-ink-900 tabular" x-text="money(p.sell_price)"></td>
                                <td class="px-5 py-3 text-right font-mono text-jade-700 tabular" x-text="p.margin + '%'"></td>
                                <td class="px-5 py-3 text-right">
                                    <p class="font-mono font-bold text-ink-900 tabular" x-text="p.stock_qty"></p>
                                    <p class="font-mono text-[11px] text-ink-400">{{ __('products.reorder_at', ['n' => '']) }}<span x-text="p.reorder_threshold"></span></p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                          :class="p.stock_qty === 0 ? 'bg-chili-50 text-chili-700' : (p.is_low ? 'bg-amber-50 text-amber-700' : 'bg-jade-50 text-jade-700')"
                                          x-text="p.stock_qty === 0 ? '{{ __('common.status.out_of_stock') }}' : (p.is_low ? '{{ __('common.status.low_stock') }}' : '{{ __('common.status.in_stock') }}')"></span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button @click="openEdit(p)" class="btn-ghost px-3 py-1.5 text-xs">{{ __('common.action.edit') }}</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="filtered.length === 0" class="px-5 py-12 text-center text-sm text-ink-500">—</div>
        </div>

        {{-- Stock movements --}}
        <div class="mt-8">
            <h2 class="text-lg font-bold tracking-tight text-ink-900">{{ __('products.movements.title') }}</h2>
            <div class="card mt-3 overflow-hidden">
                <div class="overflow-x-auto scroll-slim">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-ink/[.06] bg-paper/60 text-xs uppercase tracking-wide text-ink-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">{{ __('products.movements.type') }}</th>
                                <th class="px-5 py-3 font-semibold">{{ __('products.movements.product') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('products.movements.change') }}</th>
                                <th class="px-5 py-3 font-semibold">{{ __('products.movements.note') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('products.movements.when') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/[.05]">
                            @foreach ($movements as $m)
                                @php
                                    $typeMeta = [
                                        'sale' => ['amber', __('products.movements.sale')],
                                        'restock' => ['jade', __('products.movements.restock')],
                                        'adjustment' => ['ink', __('products.movements.adjustment')],
                                    ][$m['type']];
                                @endphp
                                <tr class="hover:bg-paper/50">
                                    <td class="px-5 py-3"><x-badge :variant="$typeMeta[0]" dot>{{ $typeMeta[1] }}</x-badge></td>
                                    <td class="px-5 py-3 font-medium text-ink-800">{{ $m['product'] }}</td>
                                    <td class="px-5 py-3 text-right font-mono font-semibold tabular {{ $m['qty_change'] < 0 ? 'text-chili-600' : 'text-jade-700' }}">{{ $m['qty_change'] > 0 ? '+' : '' }}{{ $m['qty_change'] }}</td>
                                    <td class="px-5 py-3 font-mono text-xs text-ink-500">{{ $m['note'] }}</td>
                                    <td class="px-5 py-3 text-right text-xs text-ink-500">{{ $m['at']->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== Slide-over form ===== --}}
        <div x-show="panel" x-transition.opacity @keydown.escape.window="panel = false" class="fixed inset-0 z-50" style="display:none">
            <div class="absolute inset-0 bg-ink-900/40" @click="panel = false"></div>
            <div x-show="panel" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                 class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-lift">
                <div class="flex items-center justify-between border-b border-ink/[.06] px-6 py-4">
                    <h2 class="font-bold text-ink-900" x-text="editing ? '{{ __('products.form.edit_title') }}' : '{{ __('products.form.new_title') }}'"></h2>
                    <button @click="panel = false" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink/5"><x-icon name="x" class="h-5 w-5" /></button>
                </div>

                <form @submit.prevent="save()" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5 scroll-slim">
                        {{-- image --}}
                        <div>
                            <label class="label">{{ __('products.form.image') }}</label>
                            <div class="flex items-center gap-4">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-paper text-3xl">
                                    <template x-if="imagePreview"><img :src="imagePreview" class="h-full w-full object-cover" alt=""></template>
                                    <template x-if="!imagePreview"><span x-text="editing ? editing.emoji : '📦'"></span></template>
                                </div>
                                <label class="btn-outline cursor-pointer">
                                    <x-icon name="plus" class="h-4 w-4" /> {{ __('products.form.image') }}
                                    <input type="file" accept="image/*" class="hidden" @change="preview($event)">
                                </label>
                            </div>
                            <p class="mt-1.5 text-xs text-ink-400">{{ __('products.form.image_hint') }}</p>
                        </div>

                        <div>
                            <label class="label">{{ __('products.form.name') }}</label>
                            <input type="text" class="input" :value="editing?.name ?? ''" placeholder="{{ __('products.form.name') }}">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label">{{ __('products.form.sku') }}</label>
                                <input type="text" class="input font-mono" :value="editing?.sku ?? ''">
                            </div>
                            <div>
                                <label class="label">{{ __('products.form.barcode') }}</label>
                                <input type="text" class="input font-mono" :value="editing?.barcode ?? ''">
                            </div>
                        </div>
                        <div>
                            <label class="label">{{ __('products.form.category') }}</label>
                            <input type="text" class="input" :value="editing?.category ?? ''" list="cats">
                            <datalist id="cats">
                                @foreach (\App\Support\MockData::categories() as $c)
                                    <option value="{{ $c }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label">{{ __('products.form.cost_price') }}</label>
                                <input type="number" class="input font-mono tabular" :value="editing?.cost_price ?? ''">
                            </div>
                            <div>
                                <label class="label">{{ __('products.form.sell_price') }}</label>
                                <input type="number" class="input font-mono tabular" :value="editing?.sell_price ?? ''">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label">{{ __('products.form.stock_qty') }}</label>
                                <input type="number" class="input font-mono tabular" :value="editing?.stock_qty ?? ''">
                            </div>
                            <div>
                                <label class="label">{{ __('products.form.reorder_threshold') }}</label>
                                <input type="number" class="input font-mono tabular" :value="editing?.reorder_threshold ?? ''">
                            </div>
                        </div>
                        <label class="flex items-center gap-2.5 text-sm font-medium text-ink-700">
                            <input type="checkbox" checked class="h-4 w-4 rounded border-ink/25 text-jade focus:ring-jade">
                            {{ __('products.form.active') }}
                        </label>
                    </div>
                    <div class="flex gap-2 border-t border-ink/[.06] px-6 py-4">
                        <button type="button" @click="panel = false" class="btn-outline flex-1">{{ __('common.action.cancel') }}</button>
                        <button type="submit" class="btn-primary flex-1">{{ __('products.form.save') }}</button>
                    </div>
                </form>
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
