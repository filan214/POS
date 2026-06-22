<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::active()
            ->orderBy('name')
            ->get()
            ->values();

        return view('pos.index', [
            'products' => $products,
            'shift' => $request->user()->openShift(),
        ]);
    }

    /**
     * Persist a completed sale, its line items and the per-product stock
     * movements — all inside one DB transaction so stock never lands in a
     * partial state (PRD §5.1, §10 atomicity).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,qris,debit'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $shift = $request->user()->openShift();

        if (! $shift) {
            throw ValidationException::withMessages([
                'shift' => __('pos.errors.no_shift'),
            ]);
        }

        $sale = DB::transaction(function () use ($data, $request, $shift) {
            // Lock the involved product rows for the duration of the txn.
            $ids = collect($data['items'])->pluck('id')->all();
            $products = Product::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            $total = 0;
            $lines = [];

            foreach ($data['items'] as $item) {
                $product = $products->get($item['id']);
                $qty = (int) $item['qty'];

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => __('pos.errors.unavailable', ['name' => $product->name ?? '#'.$item['id']]),
                    ]);
                }

                if ($product->stock_qty < $qty) {
                    throw ValidationException::withMessages([
                        'items' => __('pos.errors.insufficient_stock', ['name' => $product->name]),
                    ]);
                }

                $subtotal = $product->sell_price * $qty;
                $total += $subtotal;
                $lines[] = compact('product', 'qty', 'subtotal');
            }

            $method = $data['payment_method'];
            $paid = $method === 'cash' ? (int) $data['paid_amount'] : $total;

            if ($method === 'cash' && $paid < $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => __('pos.errors.underpaid'),
                ]);
            }

            $sale = Sale::create([
                'code' => $this->nextCode(),
                'shift_id' => $shift->id,
                'cashier_id' => $request->user()->id,
                'total' => $total,
                'payment_method' => $method,
                'paid_amount' => $paid,
                'change_amount' => $paid - $total,
                'status' => 'completed',
            ]);

            foreach ($lines as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'unit_price' => $line['product']->sell_price,
                    'cost_price_snapshot' => $line['product']->cost_price,
                    'subtotal' => $line['subtotal'],
                ]);

                $line['product']->decrement('stock_qty', $line['qty']);

                StockMovement::create([
                    'product_id' => $line['product']->id,
                    'type' => 'sale',
                    'qty_change' => -$line['qty'],
                    'reference_id' => $sale->id,
                    'note' => $sale->code,
                ]);
            }

            return $sale;
        });

        return response()->json([
            'ok' => true,
            'sale' => [
                'code' => $sale->code,
                'total' => $sale->total,
                'change' => $sale->change_amount,
            ],
        ]);
    }

    /** Sequential, human-friendly transaction code. */
    private function nextCode(): string
    {
        $last = Sale::max('id') ?? 2000;

        return 'TRX-'.($last + 1);
    }
}
