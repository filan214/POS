/**
 * Timing-based barcode keystroke buffer.
 *
 * A USB/Bluetooth HID barcode scanner behaves like a keyboard that "types"
 * the code far faster than a human and finishes with Enter. We watch global
 * keydown events: characters arriving within `threshold` ms of each other are
 * treated as scanner input, and Enter flushes the buffer to `onScan`.
 *
 * No scanner-specific driver or library is required — this is the framework
 * agnostic approach described in the PRD (section 5.6).
 */
export function createBarcodeBuffer({ onScan, threshold = 40, minLength = 3 } = {}) {
    let buffer = '';
    let lastTime = 0;

    return function handleKeydown(event) {
        // Ignore typing inside form fields so manual search still works.
        const tag = (event.target?.tagName || '').toLowerCase();
        const isField = tag === 'input' || tag === 'textarea' || event.target?.isContentEditable;

        const now = Date.now();
        const gap = now - lastTime;
        lastTime = now;

        if (event.key === 'Enter') {
            if (buffer.length >= minLength && !isField) {
                onScan(buffer);
                event.preventDefault();
            }
            buffer = '';
            return;
        }

        // Single printable character.
        if (event.key.length === 1) {
            // A slow gap means a human is typing — reset the buffer.
            if (gap > threshold) buffer = '';
            buffer += event.key;
        }
    };
}

/**
 * Standalone Alpine component wrapper around the buffer, kept for reuse and to
 * demonstrate the listener in isolation.
 */
export default function barcodeListener(onScan) {
    return {
        init() {
            const handler = createBarcodeBuffer({ onScan: (code) => onScan(code) });
            window.addEventListener('keydown', handler);
            this.$cleanup?.(() => window.removeEventListener('keydown', handler));
        },
    };
}
