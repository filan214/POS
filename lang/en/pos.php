<?php

return [
    'title' => 'Cashier',
    'subtitle' => 'Scan or search to add items to the sale.',

    'search_placeholder' => 'Scan barcode or search by name / SKU…',
    'scan_ready' => 'Scanner ready',
    'all' => 'All',

    'cart_title' => 'Current sale',
    'clear' => 'Clear',
    'empty_title' => 'No items yet',
    'empty_sub' => 'Scan a barcode or tap a product to begin.',

    'qty' => 'Qty',
    'each' => 'each',
    'items_in_cart' => ':count items',
    'subtotal' => 'Subtotal',
    'total' => 'Total',
    'charge' => 'Charge :amount',

    'pay_title' => 'Take payment',
    'amount_due' => 'Amount due',
    'amount_received' => 'Amount received',
    'quick_cash' => 'Quick cash',
    'exact' => 'Exact',
    'change_due' => 'Change',
    'complete_sale' => 'Complete sale',
    'back_to_cart' => 'Back to cart',
    'insufficient' => 'Received amount is below the total.',

    'done_title' => 'Payment complete',
    'done_sub' => 'Stock has been updated and the receipt is ready.',
    'new_sale' => 'New sale',
    'print_receipt' => 'Print receipt',

    'printer' => [
        'connect' => 'Connect thermal printer',
        'connected' => 'Thermal printer connected',
    ],

    'receipt' => [
        'store' => 'Warung Tanti',
        'address' => 'Jogonalan Lor RT 04, Tirtonirmolo, Kasihan, Bantul',
        'phone' => '081391349750',
        'cashier' => 'Cashier',
        'date' => 'Date',
        'no' => 'No.',
        'item' => 'Item',
        'subtotal' => 'Subtotal',
        'total' => 'TOTAL',
        'paid' => 'Paid',
        'change' => 'Change',
        'method' => 'Method',
        'thanks' => 'Thank you, see you again!',
        'footer' => 'Goods purchased cannot be exchanged',
        'promo' => 'Powered by Lapak POS',
    ],

    // Strings surfaced from JavaScript.
    'js' => [
        'added' => 'added',
        'out_of_stock' => 'Out of stock',
        'stock_limit' => 'Reached available stock',
        'unknown_barcode' => 'Unknown barcode',
        'saved' => 'Sale completed',
        'sale_failed' => 'Could not complete the sale',
        'printer_connected' => 'Printer connected',
        'printer_printed' => 'Receipt sent to printer',
        'printer_failed' => 'Printer unavailable — opening the print dialog',
        'printer_unsupported' => 'Direct printing needs Chrome or Edge',
    ],

    'no_shift_title' => 'Open a shift to start selling',
    'no_shift_sub' => 'Sales must be tied to an open till. Open your shift first.',
    'go_to_shifts' => 'Go to shifts',

    'errors' => [
        'no_shift' => 'No open shift. Open the till before selling.',
        'unavailable' => ':name is no longer available.',
        'insufficient_stock' => 'Not enough stock for :name.',
        'underpaid' => 'Received amount is below the total.',
    ],
];
