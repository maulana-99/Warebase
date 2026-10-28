<?php

namespace App\Http\Controllers;

use App\Http\Requests\Products\ProductRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Products. Everyone can list; only admins can change (see routes/web.php).
 * Stock is never edited here: it moves only through stock transactions.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        // Escape LIKE wildcards so "%" or "_" are searched literally.
        $pattern = '%'.addcslashes($search, '%_\\').'%';
        $categoryId = $request->integer('category') ?: null;
        $lowOnly = $request->boolean('low');

        $products = Product::query()
            ->with(['category', 'location'])
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('sku', 'like', $pattern)
                ->orWhere('name', 'like', $pattern)))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($lowOnly, fn ($query) => $query->lowStock())
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'categoryId' => $categoryId,
            'lowOnly' => $lowOnly,
        ]);
    }

    public function create(): View
    {
        return view('products.create', ['product' => new Product(['min_stock' => 0]), ...$this->options()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());
        $this->audit('product.created', "Menambah produk {$product->sku} {$product->name}", $product);

        return redirect()->route('products.index')->with('success', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function edit(Product $product): View
    {
        return view('products.edit', ['product' => $product, ...$this->options()]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $before = $product->getAttributes();
        $product->update($request->validated());
        $this->audit('product.updated', "Mengubah produk {$product->sku} {$product->name}", $product, $this->changes($product, $before));

        return redirect()->route('products.index')->with('success', "Produk {$product->name} berhasil diperbarui.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        // A product with stock or history stays, so the stock card and past transactions remain readable.
        if ($product->stock > 0 || $product->transactionItems()->exists()) {
            return back()->with('error', "Produk {$product->name} tidak dapat dihapus karena masih memiliki stok atau riwayat transaksi.");
        }

        // No history, so remove it for real. A soft delete would keep the unique SKU reserved.
        $product->forceDelete();
        $this->audit('product.deleted', "Menghapus produk {$product->sku} {$product->name}");

        return redirect()->route('products.index')->with('success', "Produk {$product->name} berhasil dihapus.");
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'locations' => Location::orderBy('code')->get(['id', 'code', 'name']),
        ];
    }
}
