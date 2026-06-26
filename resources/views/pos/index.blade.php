@php
    $cashierName = auth()->user()->name;
    $t = [
        'all' => __('pos.all'),
        'added' => __('pos.js.added'),
        'out_of_stock' => __('pos.js.out_of_stock'),
        'stock_limit' => __('pos.js.stock_limit'),
        'unknown_barcode' => __('pos.js.unknown_barcode'),
        'sale_failed' => __('pos.js.sale_failed'),
        'no_shift' => __('pos.errors.no_shift'),
        'low' => __('common.status.low_stock'),
        'out' => __('common.status.out_of_stock'),
        'pcs' => __('common.misc.pcs'),
        'printer_connected' => __('pos.js.printer_connected'),
        'printer_failed' => __('pos.js.printer_failed'),
        'printer_printed' => __('pos.js.printer_printed'),
        'printer_unsupported' => __('pos.js.printer_unsupported'),
    ];

    // Receipt metadata for the thermal (ESC/POS) printer path.
    $receipt = [
        'store' => [
            'name' => __('pos.receipt.store'),
            'address' => __('pos.receipt.address'),
            'phone' => __('pos.receipt.phone'),
        ],
        'labels' => [
            'no' => __('pos.receipt.no'),
            'date' => __('pos.receipt.date'),
            'cashier' => __('pos.receipt.cashier'),
            'total' => __('pos.receipt.total'),
            'method' => __('pos.receipt.method'),
            'paid' => __('pos.receipt.paid'),
            'change' => __('pos.receipt.change'),
        ],
        'methods' => [
            'cash' => __('common.payment.cash'),
            'qris' => __('common.payment.qris'),
            'debit' => __('common.payment.debit'),
        ],
        'cashier' => $cashierName,
        'dateLocale' => app()->getLocale() === 'en' ? 'en-GB' : 'id-ID',
        'thanks' => __('pos.receipt.thanks'),
        'footer' => __('pos.receipt.footer'),
        'promo' => __('pos.receipt.promo'),
    ];
@endphp

<x-app-layout :title="__('pos.title')" active="pos">
    @unless ($shift)
        <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-amber/30 bg-amber-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber/15 text-amber-700"><x-icon name="alert" class="h-5 w-5" /></span>
                <div>
                    <p class="font-semibold text-ink-900">{{ __('pos.no_shift_title') }}</p>
                    <p class="text-sm text-ink-600">{{ __('pos.no_shift_sub') }}</p>
                </div>
            </div>
            <a href="{{ route('shifts') }}" class="btn-primary shrink-0">{{ __('pos.go_to_shifts') }}</a>
        </div>
    @endunless

    <div x-data="posCart({{ Js::from([
            'products' => $products,
            't' => $t,
            'saleUrl' => route('pos.sale'),
            'csrf' => csrf_token(),
            'hasShift' => (bool) $shift,
            'receipt' => $receipt,
         ]) }})"
         class="grid gap-6 lg:grid-cols-[1fr_400px] xl:grid-cols-[1fr_420px]">

        {{-- ============ Catalogue ============ --}}
        <section class="min-w-0">
            <x-page-header :title="__('pos.title')" :subtitle="__('pos.subtitle')" class="mb-5" />

            {{-- Search + scanner status --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
                    <input type="text" x-model="search" @keydown.enter.prevent="submitSearch()"
                           placeholder="{{ __('pos.search_placeholder') }}"
                           class="input pl-11 pr-3 py-3" autofocus>
                </label>
                <div class="inline-flex items-center gap-2 rounded-xl border border-jade/20 bg-jade-50 px-3.5 py-2.5 text-sm font-semibold text-jade-700">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-pulse-dot rounded-full bg-jade"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-jade"></span>
                    </span>
                    {{ __('pos.scan_ready') }}
                </div>
            </div>

            {{-- Category chips --}}
            <div class="mt-4 flex flex-wrap gap-2">
                <template x-for="c in categories" :key="c">
                    <button @click="category = c"
                            class="rounded-full border px-3.5 py-1.5 text-sm font-semibold transition-colors"
                            :class="category === c ? 'border-ink bg-ink text-white' : 'border-ink/10 bg-white text-ink-600 hover:border-ink/25'">
                        <span x-text="c === 'all' ? t.all : c"></span>
                    </button>
                </template>
            </div>

            {{-- Product grid --}}
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                <template x-for="p in filtered" :key="p.id">
                    <button @click="add(p)" :disabled="p.stock_qty === 0"
                            class="card group flex flex-col p-3 text-left transition-all duration-150 enabled:hover:-translate-y-0.5 enabled:hover:shadow-lift disabled:cursor-not-allowed disabled:opacity-55">
                        <div class="flex items-start justify-between">
                            <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-paper text-2xl">
                                <template x-if="p.image_path"><img :src="'/' + p.image_path" class="h-full w-full object-cover" alt=""></template>
                                <template x-if="!p.image_path"><span x-text="p.emoji || '📦'"></span></template>
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold tabular"
                                  :class="p.stock_qty === 0 ? 'bg-chili-50 text-chili-700' : (p.is_low ? 'bg-amber-50 text-amber-700' : 'bg-jade-50 text-jade-700')"
                                  x-text="p.stock_qty === 0 ? t.out : (p.is_low ? (t.low) : (p.stock_qty + ' ' + t.pcs))"></span>
                        </div>
                        <p class="mt-2.5 line-clamp-2 text-sm font-semibold leading-snug text-ink-900" x-text="p.name"></p>
                        <p class="font-mono text-[11px] text-ink-400" x-text="p.sku"></p>
                        <p class="mt-auto pt-2 font-mono text-base font-bold text-jade-700 tabular" x-text="money(p.sell_price)"></p>
                    </button>
                </template>
            </div>

            <div x-show="filtered.length === 0" class="card mt-5 flex flex-col items-center justify-center gap-2 p-12 text-center">
                <x-icon name="search" class="h-8 w-8 text-ink-300" />
                <p class="text-sm text-ink-500" x-text="'“' + search + '”'"></p>
            </div>
        </section>

        {{-- ============ Cart / checkout panel ============ --}}
        <aside class="lg:sticky lg:top-[84px] lg:self-start">
            <div class="card flex max-h-[calc(100vh-110px)] flex-col overflow-hidden">

                {{-- ---- STAGE: CART ---- --}}
                <template x-if="stage === 'cart'">
                    <div class="flex min-h-0 flex-1 flex-col">
                        <div class="flex items-center justify-between border-b border-ink/[.06] px-5 py-4">
                            <div class="flex items-center gap-2">
                                <h2 class="font-bold text-ink-900">{{ __('pos.cart_title') }}</h2>
                                <span x-show="count > 0" class="badge-jade" x-text="count"></span>
                            </div>
                            <button x-show="items.length > 0" @click="clear()" class="text-sm font-semibold text-chili-600 hover:text-chili-700">{{ __('pos.clear') }}</button>
                        </div>

                        {{-- empty --}}
                        <div x-show="items.length === 0" class="flex flex-1 flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-paper"><x-icon name="pos" class="h-7 w-7 text-ink-300" /></span>
                            <div>
                                <p class="font-semibold text-ink-800">{{ __('pos.empty_title') }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ __('pos.empty_sub') }}</p>
                            </div>
                        </div>

                        {{-- items --}}
                        <div x-show="items.length > 0" class="min-h-0 flex-1 divide-y divide-ink/[.05] overflow-y-auto scroll-slim">
                            <template x-for="line in items" :key="line.id">
                                <div class="flex items-center gap-3 px-5 py-3 animate-slide-up">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-ink-900" x-text="line.name"></p>
                                        <p class="font-mono text-xs text-ink-400"><span x-text="money(line.unit_price)"></span> {{ __('pos.each') }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 rounded-lg border border-ink/10 p-0.5">
                                        <button @click="dec(line)" class="flex h-7 w-7 items-center justify-center rounded-md text-ink-600 hover:bg-ink/5"><x-icon name="minus" class="h-4 w-4" /></button>
                                        <span class="w-7 text-center font-mono text-sm font-bold tabular" x-text="line.qty"></span>
                                        <button @click="inc(line)" :disabled="line.qty >= line.stock_qty" class="flex h-7 w-7 items-center justify-center rounded-md text-ink-600 hover:bg-ink/5 disabled:opacity-30"><x-icon name="plus" class="h-4 w-4" /></button>
                                    </div>
                                    <p class="w-20 text-right font-mono text-sm font-bold text-ink-900 tabular" x-text="money(line.unit_price * line.qty)"></p>
                                </div>
                            </template>
                        </div>

                        {{-- totals --}}
                        <div x-show="items.length > 0" class="border-t border-ink/[.06] px-5 py-4">
                            <div class="flex items-center justify-between text-sm text-ink-500">
                                <span>{{ __('pos.subtotal') }}</span>
                                <span class="font-mono tabular" x-text="money(subtotal)"></span>
                            </div>
                            <div class="mt-2 flex items-end justify-between">
                                <span class="font-semibold text-ink-900">{{ __('pos.total') }}</span>
                                <span class="font-mono text-2xl font-extrabold text-ink-900 tabular" x-text="money(total)"></span>
                            </div>
                            <button @click="goToPay()" class="btn-primary mt-4 w-full text-base">
                                <x-icon name="banknotes" class="h-5 w-5" />
                                <span x-text="('{{ __('pos.charge', ['amount' => '%s']) }}').replace('%s', money(total))"></span>
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ---- STAGE: PAY ---- --}}
                <template x-if="stage === 'pay'">
                    <div class="flex min-h-0 flex-1 flex-col">
                        <div class="flex items-center gap-3 border-b border-ink/[.06] px-5 py-4">
                            <button @click="backToCart()" class="rounded-lg p-1.5 text-ink-600 hover:bg-ink/5"><x-icon name="arrow-left" class="h-5 w-5" /></button>
                            <h2 class="font-bold text-ink-900">{{ __('pos.pay_title') }}</h2>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 scroll-slim">
                            <div class="rounded-2xl bg-ink px-5 py-4 text-center text-white">
                                <p class="text-xs font-medium uppercase tracking-wide text-white/55">{{ __('pos.amount_due') }}</p>
                                <p class="mt-1 font-mono text-3xl font-extrabold tabular" x-text="money(total)"></p>
                            </div>

                            {{-- method --}}
                            <p class="label mt-5">{{ __('common.payment.method') }}</p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['cash' => 'banknotes', 'qris' => 'qr', 'debit' => 'card'] as $method => $icon)
                                    <button @click="paymentMethod = '{{ $method }}'; if ('{{ $method }}' !== 'cash') paidAmount = String(total)"
                                            class="flex flex-col items-center gap-1.5 rounded-xl border px-2 py-3 text-xs font-semibold transition-colors"
                                            :class="paymentMethod === '{{ $method }}' ? 'border-jade bg-jade-50 text-jade-700' : 'border-ink/10 text-ink-600 hover:border-ink/25'">
                                        <x-icon name="{{ $icon }}" class="h-5 w-5" />
                                        {{ __('common.payment.'.$method) }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- cash tendering --}}
                            <div x-show="paymentMethod === 'cash'" class="mt-5">
                                <label class="label">{{ __('pos.amount_received') }}</label>
                                <input type="number" inputmode="numeric" x-model="paidAmount" placeholder="0"
                                       class="input font-mono text-lg font-bold tabular">
                                <div class="mt-3 grid grid-cols-4 gap-2">
                                    <template x-for="amt in suggestedTenders" :key="amt">
                                        <button @click="tender(amt)" class="rounded-lg border border-ink/10 px-1 py-2 font-mono text-xs font-semibold text-ink-700 hover:border-jade hover:text-jade-700 tabular"
                                                x-text="amt === total ? '{{ __('pos.exact') }}' : money(amt)"></button>
                                    </template>
                                </div>
                                <div class="mt-4 flex items-center justify-between rounded-xl bg-paper px-4 py-3">
                                    <span class="text-sm font-semibold text-ink-700">{{ __('pos.change_due') }}</span>
                                    <span class="font-mono text-xl font-extrabold tabular" :class="change > 0 ? 'text-jade-700' : 'text-ink-900'" x-text="money(change)"></span>
                                </div>
                                <p x-show="paid > 0 && paid < total" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-chili-600">
                                    <x-icon name="alert" class="h-4 w-4" /> {{ __('pos.insufficient') }}
                                </p>
                            </div>

                            <div x-show="paymentMethod !== 'cash'" class="mt-5 flex items-center gap-2 rounded-xl bg-paper px-4 py-3 text-sm text-ink-600">
                                <x-icon name="check-circle" class="h-5 w-5 text-jade-600" />
                                <span x-text="(paymentMethod === 'qris' ? '{{ __('common.payment.qris') }}' : '{{ __('common.payment.debit') }}') + ' · ' + money(total)"></span>
                            </div>
                        </div>

                        <div class="border-t border-ink/[.06] px-5 py-4">
                            <button @click="complete()" :disabled="!canComplete || submitting" class="btn-primary w-full text-base">
                                <x-icon name="check" class="h-5 w-5" /> {{ __('pos.complete_sale') }}
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ---- STAGE: DONE (receipt) ---- --}}
                <template x-if="stage === 'done'">
                    <div class="flex min-h-0 flex-1 flex-col">
                        <div class="flex flex-col items-center gap-1 px-5 pt-6 text-center no-print">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-jade-50"><x-icon name="check-circle" class="h-7 w-7 text-jade-600" /></span>
                            <h2 class="mt-1 font-bold text-ink-900">{{ __('pos.done_title') }}</h2>
                            <p class="text-sm text-ink-500">{{ __('pos.done_sub') }}</p>
                        </div>

                        {{-- Receipt — the signature artifact --}}
                        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-6 scroll-slim">
                            <div class="print-area receipt-paper mx-auto max-w-[300px] px-5 py-5 font-mono text-[12px] leading-relaxed text-ink-800">
                                <div class="text-center">
                                    <p class="text-sm font-bold tracking-wide">{{ __('pos.receipt.store') }}</p>
                                    <p class="text-[11px] text-ink-500">{{ __('pos.receipt.address') }}</p>
                                    <p class="text-[11px] text-ink-500">{{ __('pos.receipt.phone') }}</p>
                                </div>
                                <div class="receipt-rule my-3"></div>
                                <div class="space-y-0.5 text-[11px]">
                                    <div class="flex justify-between"><span>{{ __('pos.receipt.no') }}</span><span x-text="receiptNo"></span></div>
                                    <div class="flex justify-between"><span>{{ __('pos.receipt.date') }}</span><span x-text="now.toLocaleString('{{ app()->getLocale() === 'en' ? 'en-GB' : 'id-ID' }}')"></span></div>
                                    <div class="flex justify-between"><span>{{ __('pos.receipt.cashier') }}</span><span>{{ $cashierName }}</span></div>
                                </div>
                                <div class="receipt-rule my-3"></div>
                                <div class="space-y-1.5">
                                    <template x-for="line in items" :key="line.id">
                                        <div>
                                            <p x-text="line.name"></p>
                                            <div class="flex justify-between text-ink-600">
                                                <span x-text="line.qty + ' x ' + money(line.unit_price)"></span>
                                                <span x-text="money(line.unit_price * line.qty)"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="receipt-rule my-3"></div>
                                <div class="flex justify-between text-sm font-bold">
                                    <span>{{ __('pos.receipt.total') }}</span>
                                    <span x-text="money(total)"></span>
                                </div>
                                <div class="mt-1.5 space-y-0.5 text-[11px] text-ink-600">
                                    <div class="flex justify-between"><span>{{ __('pos.receipt.method') }}</span><span x-text="({cash:'{{ __('common.payment.cash') }}',qris:'{{ __('common.payment.qris') }}',debit:'{{ __('common.payment.debit') }}'})[paymentMethod]"></span></div>
                                    <div class="flex justify-between" x-show="paymentMethod === 'cash'"><span>{{ __('pos.receipt.paid') }}</span><span x-text="money(paid)"></span></div>
                                    <div class="flex justify-between" x-show="paymentMethod === 'cash'"><span>{{ __('pos.receipt.change') }}</span><span x-text="money(change)"></span></div>
                                </div>
                                <div class="receipt-rule my-3"></div>
                                <p class="text-center text-[11px] font-semibold">{{ __('pos.receipt.thanks') }}</p>
                                <p class="mt-1 text-center text-[10px] text-ink-400">{{ __('pos.receipt.footer') }}</p>
                            </div>
                        </div>

                        {{-- Thermal printer (WebUSB) pairing — only where the browser supports it --}}
                        <div class="px-5 pt-3 no-print" x-show="printerSupported" x-cloak>
                            <button x-show="!printerConnected" @click="connectPrinter()"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500 hover:text-ink-800 transition-colors">
                                <x-icon name="printer" class="h-4 w-4" /> {{ __('pos.printer.connect') }}
                            </button>
                            <span x-show="printerConnected" class="inline-flex items-center gap-1.5 text-xs font-semibold text-jade-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-jade"></span> {{ __('pos.printer.connected') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 border-t border-ink/[.06] px-5 py-4 no-print">
                            <button @click="printReceipt()" :disabled="printerBusy" class="btn-outline disabled:opacity-60"><x-icon name="printer" class="h-5 w-5" /> {{ __('pos.print_receipt') }}</button>
                            <button @click="newSale()" class="btn-primary"><x-icon name="plus" class="h-5 w-5" /> {{ __('pos.new_sale') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </aside>

        {{-- Toast --}}
        <div x-show="toast" x-transition.opacity
             class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 no-print" style="display:none">
            <div class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-lift"
                 :class="toast && toast.type === 'error' ? 'bg-chili' : 'bg-ink'">
                <template x-if="toast && toast.type !== 'error'"><x-icon name="check" class="h-4 w-4" /></template>
                <template x-if="toast && toast.type === 'error'"><x-icon name="alert" class="h-4 w-4" /></template>
                <span x-text="toast?.message"></span>
            </div>
        </div>
    </div>
</x-app-layout>
