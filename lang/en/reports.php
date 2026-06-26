<?php

return [
    'title' => 'Reports',
    'subtitle' => 'Sales, profit and shift performance.',
    'greeting' => 'Good day, :name',
    'export_title' => 'Sales Report',
    'generated' => 'Generated',

    'range' => [
        'today' => 'Today',
        'last_7' => 'Last 7 days',
        'last_30' => 'Last 30 days',
        'month' => 'This month',
        'custom' => 'Custom',
        'from' => 'From',
        'to' => 'To',
    ],

    'stat' => [
        'sales' => 'Total sales',
        'profit' => 'Gross profit',
        'transactions' => 'Transactions',
        'basket' => 'Avg. basket',
        'margin' => 'Profit margin',
        'vs_prev' => 'vs previous period',
    ],

    'chart' => [
        'trend' => 'Sales over time',
        'trend_sub' => 'Daily revenue for the selected range',
        'trend_sub_today' => 'Hourly revenue today',
        'category' => 'Sales by category',
        'category_sub' => 'Share of revenue',
    ],

    'top' => [
        'title' => 'Best sellers',
        'product' => 'Product',
        'sold' => 'Sold',
        'revenue' => 'Revenue',
    ],

    'low' => [
        'title' => 'Low stock alerts',
        'sub' => 'At or below reorder threshold',
        'none' => 'Everything is well stocked.',
        'left' => ':n left',
    ],

    'recon' => [
        'title' => 'Shift reconciliation',
        'sub' => 'Expected vs. counted cash per shift',
        'cashier' => 'Cashier',
        'expected' => 'Expected',
        'actual' => 'Counted',
        'diff' => 'Difference',
    ],

    'recent' => [
        'title' => 'Recent transactions',
        'no' => 'No.',
        'time' => 'Time',
        'method' => 'Method',
        'items' => 'Items',
        'total' => 'Total',
    ],

    'void' => [
        'action' => 'Void',
        'confirm' => 'Void sale :code? Stock will be restored and this cannot be undone.',
        'done' => 'Sale :code was voided and stock restored.',
        'already' => 'Sale :code is not a completed sale, so it cannot be voided.',
    ],
];
