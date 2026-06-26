import './bootstrap';

import Alpine from 'alpinejs';

import posCart from './stores/cart';
import barcodeListener from './stores/barcode';

// Expose component factories so Blade `x-data` can reference them by name.
window.posCart = posCart;
window.barcodeListener = barcodeListener;

window.Alpine = Alpine;
Alpine.start();
