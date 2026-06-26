/**
 * ESC/POS receipt encoder — pure, framework-agnostic, browser-free.
 *
 * Turns a plain receipt object into the byte stream a thermal printer speaks
 * (ESC/POS), ready to push over WebUSB (see ./webusb.js). Kept free of any DOM
 * or `navigator` reference so it runs — and is unit-tested — under plain Node.
 *
 * Reference: Epson ESC/POS command set (the de-facto standard most cheap
 * 58mm/80mm thermal printers implement).
 */

// ---- Raw command sequences ------------------------------------------------
const ESC = 0x1b;
const GS = 0x1d;

const CMD = {
    INIT: [ESC, 0x40], // ESC @  — reset to power-on defaults
    ALIGN_LEFT: [ESC, 0x61, 0x00],
    ALIGN_CENTER: [ESC, 0x61, 0x01],
    BOLD_ON: [ESC, 0x45, 0x01],
    BOLD_OFF: [ESC, 0x45, 0x00],
    DOUBLE_ON: [GS, 0x21, 0x11], // GS ! 0x11 — double width + height
    DOUBLE_OFF: [GS, 0x21, 0x00],
    CUT: [GS, 0x56, 0x42, 0x00], // GS V B 0 — feed then partial cut
};

/** Default characters per line. 32 ≈ 58mm font A; safe on 80mm too (just narrower). */
const DEFAULT_WIDTH = 32;

/**
 * Map a string to single-byte values. ESC/POS prints from a single-byte code
 * page, so we take the low byte of each char. The receipt content here is
 * ASCII (store/product names, digits), which round-trips exactly.
 */
function bytesOf(text) {
    const out = [];
    for (let i = 0; i < text.length; i++) out.push(text.charCodeAt(i) & 0xff);
    return out;
}

/** Greedy word-wrap to `width` columns; hard-splits any word longer than width. */
export function wrapText(text, width = DEFAULT_WIDTH) {
    const words = String(text).split(/\s+/).filter(Boolean);
    const lines = [];
    let line = '';

    for (let word of words) {
        while (word.length > width) {
            if (line) {
                lines.push(line);
                line = '';
            }
            lines.push(word.slice(0, width));
            word = word.slice(width);
        }
        if (!line) {
            line = word;
        } else if (line.length + 1 + word.length <= width) {
            line += ' ' + word;
        } else {
            lines.push(line);
            line = word;
        }
    }
    if (line) lines.push(line);
    return lines.length ? lines : [''];
}

/**
 * Lay a label on the left and a value flush-right within `width` columns.
 * The returned string is always exactly `width` characters wide, so a decoded
 * receipt stays column-aligned. Over-long labels are truncated, not wrapped.
 */
export function twoCol(left, right, width = DEFAULT_WIDTH) {
    left = String(left);
    right = String(right);
    if (right.length >= width) return right.slice(0, width);
    const maxLeft = width - right.length - 1; // keep at least one space of gap
    if (left.length > maxLeft) left = left.slice(0, maxLeft);
    const gap = width - left.length - right.length;
    return left + ' '.repeat(gap) + right;
}

/**
 * Encode a receipt into an ESC/POS byte stream.
 *
 * @param {object}  receipt
 * @param {{name:string,address:string,phone:string}} receipt.store
 * @param {{no:string,date:string,cashier:string,total:string,method:string,paid:string,change:string}} receipt.labels
 * @param {{no:string,date:string,cashier:string}} receipt.values
 * @param {Array<{name:string,qty:number,unitPriceText:string,subtotalText:string}>} receipt.items
 * @param {string}  receipt.methodText
 * @param {string}  receipt.totalText
 * @param {boolean} receipt.isCash      Show paid/change only for cash sales.
 * @param {string}  receipt.paidText
 * @param {string}  receipt.changeText
 * @param {string}  receipt.thanks
 * @param {string}  receipt.footer
 * @param {string} [receipt.promo]
 * @param {{width?:number, cut?:boolean}} [opts]
 * @returns {Uint8Array}
 */
export function encodeReceipt(receipt, opts = {}) {
    const width = opts.width ?? DEFAULT_WIDTH;
    const cut = opts.cut ?? true;
    const buf = [];

    const raw = (cmd) => buf.push(...cmd);
    const text = (str) => buf.push(...bytesOf(str));
    const line = (str = '') => {
        text(str);
        buf.push(0x0a); // LF
    };
    const centered = (str) => wrapText(str, width).forEach(line);
    const divider = () => line('-'.repeat(width));

    const { store, labels, values, items } = receipt;

    raw(CMD.INIT);

    // ---- Header (centered) ----
    raw(CMD.ALIGN_CENTER);
    raw(CMD.BOLD_ON);
    raw(CMD.DOUBLE_ON);
    centered(store.name);
    raw(CMD.DOUBLE_OFF);
    raw(CMD.BOLD_OFF);
    centered(store.address);
    centered(store.phone);

    // ---- Meta (left) ----
    raw(CMD.ALIGN_LEFT);
    divider();
    line(twoCol(labels.no, values.no, width));
    line(twoCol(labels.date, values.date, width));
    line(twoCol(labels.cashier, values.cashier, width));
    divider();

    // ---- Line items ----
    for (const item of items) {
        wrapText(item.name, width).forEach(line);
        line(twoCol(`  ${item.qty} x ${item.unitPriceText}`, item.subtotalText, width));
    }
    divider();

    // ---- Totals ----
    raw(CMD.BOLD_ON);
    line(twoCol(labels.total, receipt.totalText, width));
    raw(CMD.BOLD_OFF);
    line(twoCol(labels.method, receipt.methodText, width));
    if (receipt.isCash) {
        line(twoCol(labels.paid, receipt.paidText, width));
        line(twoCol(labels.change, receipt.changeText, width));
    }
    divider();

    // ---- Footer (centered) ----
    raw(CMD.ALIGN_CENTER);
    centered(receipt.thanks);
    centered(receipt.footer);
    if (receipt.promo) {
        line('');
        centered(receipt.promo);
    }

    // Feed clear of the tear bar, then cut.
    line('');
    line('');
    if (cut) raw(CMD.CUT);

    return Uint8Array.from(buf);
}

export { CMD, DEFAULT_WIDTH };
