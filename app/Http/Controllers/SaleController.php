<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Void a completed sale (PRD §5.1 — restricted to Owner; the route sits
     * behind `role:owner`).
     *
     * Mirrors the atomic checkout in reverse: inside one transaction it locks
     * the products, restores their stock, logs a reversing stock movement per
     * line, and flips the sale to `voided` with an audit stamp. Reports and
     * shift cash reconciliation already filter on `status = 'completed'`, so a
     * voided sale drops out of every figure automatically.
     */
    public function void(Request $request, Sale $sale): RedirectResponse
    {
        // Idempotent guard: only a completed sale can be voided. Anything else
        // (already voided) is a no-op so a double submit can't restore twice.
        if (! $sale->isCompleted()) {
            return back()->with('error', __('reports.void.already', ['code' => $sale->code]));
        }

        DB::transaction(function () use ($sale, $request) {
            $sale->load('items');

            $products = Product::whereIn('id', $sale->items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($sale->items as $item) {
                if ($product = $products->get($item->product_id)) {
                    $product->increment('stock_qty', $item->qty);

                    // Logged as an 'adjustment' (the existing movement type) with
                    // a Void note — avoids a fragile cross-DB enum change while
                    // staying traceable via reference_id + note.
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'type' => 'adjustment',
                        'qty_change' => $item->qty, // positive: stock returns
                        'reference_id' => $sale->id,
                        'note' => 'Void '.$sale->code,
                    ]);
                }
            }

            $sale->update([
                'status' => 'voided',
                'voided_at' => Carbon::now(),
                'voided_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', __('reports.void.done', ['code' => $sale->code]));
    }
}
