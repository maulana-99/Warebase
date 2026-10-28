<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

/**
 * Opening stock. Goes through StockService like real goods-in, so the stock card starts consistent.
 */
class StockSeeder extends Seeder
{
    public function run(StockService $stock): void
    {
        $staff = User::where('email', 'staff@warebase.test')->firstOrFail();
        $suppliers = Supplier::orderBy('id')->get();

        Product::orderBy('id')->get()
            ->chunk(4)
            ->each(function ($products, $i) use ($stock, $staff, $suppliers) {
                $stock->recordGoodsIn($staff, [
                    'supplier_id' => $suppliers[$i % $suppliers->count()]->id,
                    'transaction_date' => now()->subDays(6 - $i)->toDateString(),
                    'note' => 'Stok awal',
                ], $products->map(fn (Product $product) => [
                    'product_id' => $product->id,
                    // Some products start below their minimum so the low-stock warning has something to show.
                    'quantity' => $product->id % 3 === 0 ? max(1, intdiv($product->min_stock, 2)) : $product->min_stock * 3,
                ])->values()->all());
            });
    }
}
