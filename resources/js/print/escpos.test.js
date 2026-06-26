/**
 * Unit tests for the pure ESC/POS encoder. Runs under Node's built-in test
 * runner — no extra dependency:  `node --test resources/js/print/`
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { encodeReceipt, twoCol, wrapText, CMD } from './escpos.js';

const sample = () => ({
    store: { name: 'Warung Tanti', address: 'Jl. Mawar No. 7, Bantul', phone: '0813-9999-0000' },
    labels: { no: 'No.', date: 'Date', cashier: 'Cashier', total: 'TOTAL', method: 'Method', paid: 'Paid', change: 'Change' },
    values: { no: 'TRX-2042', date: '26/06/2026 14:30', cashier: 'Dewi Lestari' },
    items: [
        { name: 'Indomie Goreng', qty: 2, unitPriceText: 'Rp 3.500', subtotalText: 'Rp 7.000' },
        { name: 'Aqua 600ml', qty: 1, unitPriceText: 'Rp 3.000', subtotalText: 'Rp 3.000' },
    ],
    methodText: 'Cash',
    totalText: 'Rp 10.000',
    isCash: true,
    paidText: 'Rp 20.000',
    changeText: 'Rp 10.000',
    thanks: 'Thank you, see you again!',
    footer: 'Goods purchased cannot be exchanged',
    promo: 'Powered by Lapak POS',
});

/** Decode the printable text from a byte stream (drops the ESC/GS command bytes). */
function decode(bytes) {
    let s = '';
    for (let i = 0; i < bytes.length; i++) {
        const b = bytes[i];
        if (b === 0x1b) { i += skipEsc(bytes, i); continue; }
        if (b === 0x1d) { i += skipGs(bytes, i); continue; }
        s += String.fromCharCode(b);
    }
    return s;
}
// ESC sequences used here: ESC @ (1 more byte), ESC a n / ESC E n (2 more bytes).
function skipEsc(bytes, i) {
    return bytes[i + 1] === 0x40 ? 1 : 2;
}
// GS sequences used here: GS ! n (2 more), GS V B 0 (3 more).
function skipGs(bytes, i) {
    return bytes[i + 1] === 0x56 ? 3 : 2;
}

test('returns a Uint8Array beginning with the INIT command', () => {
    const out = encodeReceipt(sample());
    assert.ok(out instanceof Uint8Array);
    assert.equal(out[0], CMD.INIT[0]);
    assert.equal(out[1], CMD.INIT[1]);
});

test('includes the store name, totals and line items as text', () => {
    const text = decode(encodeReceipt(sample()));
    assert.match(text, /Warung Tanti/);
    assert.match(text, /Indomie Goreng/);
    assert.match(text, /TRX-2042/);
    assert.match(text, /TOTAL/);
    assert.match(text, /Rp 10\.000/);
});

test('cuts the paper at the end when cut is enabled (default)', () => {
    const out = encodeReceipt(sample());
    const tail = Array.from(out.slice(-CMD.CUT.length));
    assert.deepEqual(tail, CMD.CUT);
});

test('omits the cut sequence when cut:false', () => {
    const out = encodeReceipt(sample(), { cut: false });
    const tail = Array.from(out.slice(-CMD.CUT.length));
    assert.notDeepEqual(tail, CMD.CUT);
});

test('cash-only rows (paid/change) are dropped for non-cash sales', () => {
    const card = { ...sample(), isCash: false, methodText: 'QRIS' };
    const text = decode(encodeReceipt(card));
    assert.match(text, /QRIS/);
    assert.doesNotMatch(text, /Change/);
});

test('every printed line fits within the column width', () => {
    const width = 32;
    const text = decode(encodeReceipt(sample(), { width }));
    for (const ln of text.split('\n')) {
        assert.ok(ln.length <= width, `line over ${width} cols: "${ln}" (${ln.length})`);
    }
});

test('twoCol pads to an exact width with the value flush right', () => {
    const row = twoCol('Paid', 'Rp 20.000', 32);
    assert.equal(row.length, 32);
    assert.ok(row.startsWith('Paid'));
    assert.ok(row.endsWith('Rp 20.000'));
});

test('wrapText hard-splits a word longer than the width', () => {
    const lines = wrapText('SUPERCALIFRAGILISTIC', 8);
    assert.ok(lines.every((l) => l.length <= 8));
    assert.equal(lines.join(''), 'SUPERCALIFRAGILISTIC');
});
