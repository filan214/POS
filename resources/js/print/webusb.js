/**
 * WebUSB transport for an ESC/POS thermal printer.
 *
 * The browser talks to a USB printer directly — no driver, no backend. This is
 * the primary receipt path described in the PRD (5.6); the CSS print-dialog
 * fallback in `cart.js` covers browsers/setups without WebUSB.
 *
 * WebUSB requires a secure context (https or localhost), a Chromium-based
 * browser, and a user gesture to call `requestDevice()`. The chosen device is
 * remembered by the browser, so a returning cashier can reconnect silently via
 * `reconnect()`.
 */

/** USB printer class code (bInterfaceClass = 7), used to filter the chooser. */
const PRINTER_CLASS = 0x07;

export function thermalPrinterSupported() {
    return typeof navigator !== 'undefined' && 'usb' in navigator;
}

export function createThermalPrinter() {
    let device = null;
    let endpoint = null; // bulk OUT endpoint number

    async function bind(dev) {
        await dev.open();
        if (dev.configuration === null) await dev.selectConfiguration(1);

        // Find a printer-class interface that exposes a bulk OUT endpoint.
        for (const iface of dev.configuration.interfaces) {
            const alt =
                iface.alternates.find((a) => a.interfaceClass === PRINTER_CLASS) ?? iface.alternates[0];
            const out = alt?.endpoints.find((e) => e.direction === 'out');
            if (out) {
                await dev.claimInterface(iface.interfaceNumber);
                if (alt.alternateSetting !== 0) {
                    await dev.selectAlternateInterface(iface.interfaceNumber, alt.alternateSetting);
                }
                device = dev;
                endpoint = out.endpointNumber;
                return;
            }
        }
        await dev.close();
        throw new Error('No printer interface with an OUT endpoint was found on this device.');
    }

    return {
        get connected() {
            return device !== null;
        },

        /** Open the browser's device chooser and connect to the picked printer. */
        async connect() {
            if (!thermalPrinterSupported()) {
                throw new Error('WebUSB is not supported in this browser.');
            }
            const dev = await navigator.usb.requestDevice({ filters: [{ classCode: PRINTER_CLASS }] });
            await bind(dev);
        },

        /** Reconnect silently to a previously authorised printer, if any. Returns success. */
        async reconnect() {
            if (!thermalPrinterSupported()) return false;
            const [known] = await navigator.usb.getDevices();
            if (!known) return false;
            try {
                await bind(known);
                return true;
            } catch {
                return false;
            }
        },

        /** Push a byte stream (e.g. from encodeReceipt) to the printer. */
        async print(bytes) {
            if (!device) throw new Error('Printer is not connected.');
            await device.transferOut(endpoint, bytes);
        },

        async disconnect() {
            try {
                if (device) await device.close();
            } catch {
                /* already gone */
            }
            device = null;
            endpoint = null;
        },
    };
}
