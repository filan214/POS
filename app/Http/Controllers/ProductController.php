<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Intervention\Image\Laravel\Facades\Image;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::orderBy('name')->get()->values();

        $movements = StockMovement::with('product')
            ->latest('created_at')
            ->limit(12)
            ->get();

        return view('products.index', [
            'products' => $products,
            'movements' => $movements,
            'stats' => [
                'total' => $products->count(),
                'low' => $products->where('is_low', true)->count(),
                'out' => $products->where('is_out', true)->count(),
                'value' => $products->sum(fn ($p) => $p->cost_price * $p->stock_qty),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);
        $data['image_path'] = $this->storeImage($request);
        $data['is_active'] = $request->boolean('is_active');

        Product::create($data);

        return back()->with('status', __('products.flash.created'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, $product);
        $data['is_active'] = $request->boolean('is_active');

        $beforeQty = $product->stock_qty;

        if ($path = $this->storeImage($request)) {
            $data['image_path'] = $path;
        }

        DB::transaction(function () use ($product, $data, $beforeQty) {
            $product->update($data);

            // Manual stock edits are logged as adjustments for the audit trail.
            $delta = $product->stock_qty - $beforeQty;
            if ($delta !== 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'qty_change' => $delta,
                    'note' => __('products.movements.manual'),
                ]);
            }
        });

        return back()->with('status', __('products.flash.updated'));
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'.($product ? ','.$product->id : '')],
            'barcode' => ['nullable', 'string', 'max:64', 'unique:products,barcode'.($product ? ','.$product->id : '')],
            'category' => ['required', 'string', 'max:64'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'sell_price' => ['required', 'integer', 'min:0'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'reorder_threshold' => ['required', 'integer', 'min:0'],
            'emoji' => ['nullable', 'string', 'max:16'],
            // Raster formats only — SVG is excluded to avoid stored XSS via
            // embedded scripts. Both the extension and the sniffed MIME type
            // must be on the allow-list.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
        ]);
    }

    /**
     * Store an uploaded image under public/uploads/products (no symlink — the
     * shared-hosting target can't run `storage:link`, per PRD §7).
     */
    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');

        // Only accept raster types we can safely re-encode. The MIME is sniffed
        // server-side, never trusted from the client filename — this blocks
        // executable (.php) or scriptable (.svg) uploads.
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $dir = public_path('uploads/products');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Defense in depth: disable script execution in the uploads dir on
        // Apache shared hosting (the PRD deploy target).
        $htaccess = $dir.'/.htaccess';
        if (! file_exists($htaccess)) {
            @file_put_contents($htaccess, "php_flag engine off\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps\n");
        }

        // Compress + convert to WebP via Intervention Image (pure PHP/GD, no
        // Node step — PRD §5.7). Re-encoding also strips any embedded payload.
        $name = uniqid('p_').'.webp';
        Image::read($file->getRealPath())
            ->scaleDown(width: 1000)
            ->toWebp(80)
            ->save($dir.'/'.$name);

        return 'uploads/products/'.$name;
    }
}
