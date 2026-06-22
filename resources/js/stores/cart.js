import { createBarcodeBuffer } from './barcode';

const idr = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

/**
 * The POS cart. Products are seeded from the server (real DB rows) and the
 * completed sale is POSTed back, where it is persisted atomically and stock is
 * decremented. The view contract (items, totals, checkout) is unchanged.
 *
 * @param {object} config
 * @param {Array}  config.products   Catalogue rows: {id, sku, barcode, name, category, sell_price, stock_qty}
 * @param {object} config.t          Translated strings used inside JS (e.g. toast text)
 * @param {string} config.saleUrl    POST endpoint for completing a sale
 * @param {string} config.csrf       CSRF token
 * @param {boolean} config.hasShift  Whether the cashier has an open shift
 */
export default function posCart({ products = [], t = {}, saleUrl = '', csrf = '', hasShift = false } = {}) {
    return {
        products,
        t,
        saleUrl,
        csrf,
        hasShift,
        items: [],
        search: '',
        category: 'all',
        // checkout flow: 'cart' -> 'pay' -> 'done'
        stage: 'cart',
        paymentMethod: 'cash',
        paidAmount: '',
        submitting: false,
        toast: null,
        receiptNo: null,

        init() {
            // Attach the timing-based barcode listener for this screen.
            const handler = createBarcodeBuffer({ onScan: (code) => this.scan(code) });
            window.addEventListener('keydown', handler);
        },

        // ---- Catalogue helpers -------------------------------------------
        get categories() {
            return ['all', ...new Set(this.products.map((p) => p.category))];
        },

        get filtered() {
            const q = this.search.trim().toLowerCase();
            return this.products.filter((p) => {
                const inCategory = this.category === 'all' || p.category === this.category;
                const matches =
                    !q ||
                    p.name.toLowerCase().includes(q) ||
                    p.sku.toLowerCase().includes(q) ||
                    String(p.barcode).includes(q);
                return inCategory && matches;
            });
        },

        scan(code) {
            const product = this.products.find((p) => String(p.barcode) === String(code));
            if (product) {
                this.add(product);
            } else {
                this.flash(`${this.t.unknown_barcode || 'Unknown barcode'}: ${code}`, 'error');
            }
        },

        // Enter inside the search box: match a barcode exactly, else take the
        // first result. Covers manual entry and scanners that type into a field.
        submitSearch() {
            const q = this.search.trim();
            if (!q) return;
            const byBarcode = this.products.find((p) => String(p.barcode) === q);
            if (byBarcode) {
                this.add(byBarcode);
                this.search = '';
                return;
            }
            const results = this.filtered;
            if (results.length > 0) {
                this.add(results[0]);
                this.search = '';
            } else {
                this.flash(`${this.t.unknown_barcode || 'Unknown barcode'}: ${q}`, 'error');
            }
        },

        // ---- Cart mutations ----------------------------------------------
        add(product) {
            if (product.stock_qty <= 0) {
                this.flash(this.t.out_of_stock || 'Out of stock', 'error');
                return;
            }
            const line = this.items.find((i) => i.id === product.id);
            if (line) {
                if (line.qty >= product.stock_qty) {
                    this.flash(this.t.stock_limit || 'Reached available stock', 'error');
                    return;
                }
                line.qty++;
            } else {
                this.items.unshift({
                    id: product.id,
                    name: product.name,
                    sku: product.sku,
                    unit_price: product.sell_price,
                    stock_qty: product.stock_qty,
                    qty: 1,
                });
            }
            this.flash(`${product.name} ${this.t.added || 'added'}`);
        },

        inc(line) {
            if (line.qty < line.stock_qty) line.qty++;
        },

        dec(line) {
            line.qty--;
            if (line.qty <= 0) this.remove(line.id);
        },

        remove(id) {
            this.items = this.items.filter((i) => i.id !== id);
        },

        clear() {
            this.items = [];
            this.stage = 'cart';
            this.paidAmount = '';
        },

        // ---- Money -------------------------------------------------------
        get count() {
            return this.items.reduce((n, i) => n + i.qty, 0);
        },

        get subtotal() {
            return this.items.reduce((sum, i) => sum + i.unit_price * i.qty, 0);
        },

        get total() {
            return this.subtotal;
        },

        get paid() {
            return Number(this.paidAmount) || 0;
        },

        get change() {
            return Math.max(0, this.paid - this.total);
        },

        get canComplete() {
            if (this.items.length === 0) return false;
            if (this.paymentMethod === 'cash') return this.paid >= this.total;
            return true; // QRIS / debit are recorded as exact
        },

        // Quick-tender buttons for cash.
        tender(amount) {
            this.paidAmount = String(amount);
        },

        get suggestedTenders() {
            const t = this.total;
            const rounds = [t];
            for (const step of [1000, 5000, 10000, 50000, 100000]) {
                const up = Math.ceil(t / step) * step;
                if (up > t && !rounds.includes(up)) rounds.push(up);
            }
            return rounds.slice(0, 4);
        },

        // ---- Checkout flow ----------------------------------------------
        goToPay() {
            if (this.items.length === 0) return;
            this.stage = 'pay';
            if (this.paymentMethod !== 'cash') this.paidAmount = String(this.total);
        },

        backToCart() {
            this.stage = 'cart';
        },

        async complete() {
            if (!this.canComplete || this.submitting) return;
            if (!this.hasShift) {
                this.flash(this.t.no_shift || 'No open shift', 'error');
                return;
            }

            this.submitting = true;
            try {
                const res = await fetch(this.saleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        payment_method: this.paymentMethod,
                        paid_amount: this.paymentMethod === 'cash' ? this.paid : this.total,
                        items: this.items.map((i) => ({ id: i.id, qty: i.qty })),
                    }),
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                    this.flash(firstError || data.message || this.t.sale_failed || 'Could not complete the sale', 'error');
                    return;
                }

                const data = await res.json();
                this.receiptNo = data.sale.code;

                // Reflect the new stock levels in the catalogue immediately.
                this.items.forEach((line) => {
                    const p = this.products.find((p) => p.id === line.id);
                    if (p) {
                        p.stock_qty -= line.qty;
                        p.is_out = p.stock_qty <= 0;
                        p.is_low = p.stock_qty > 0 && p.stock_qty <= p.reorder_threshold;
                    }
                });

                this.stage = 'done';
            } catch (e) {
                this.flash(this.t.sale_failed || 'Could not complete the sale', 'error');
            } finally {
                this.submitting = false;
            }
        },

        newSale() {
            this.items = [];
            this.stage = 'cart';
            this.paidAmount = '';
            this.paymentMethod = 'cash';
            this.receiptNo = null;
        },

        printReceipt() {
            window.print();
        },

        // ---- Formatting / feedback --------------------------------------
        money(value) {
            return idr.format(value || 0);
        },

        flash(message, type = 'success') {
            this.toast = { message, type };
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => (this.toast = null), 2200);
        },

        get now() {
            return new Date();
        },
    };
}
