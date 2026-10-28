<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\StockException;
use App\Models\Department;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stock = app(StockService::class);
    }

    public function test_goods_in_adds_stock_and_records_running_balance(): void
    {
        $user = User::factory()->create();
        $a = Product::factory()->create();
        $b = Product::factory()->create();
        $supplier = Transaction::factory()->make()->supplier_id;

        $first = $this->stock->recordGoodsIn($user, ['supplier_id' => $supplier, 'transaction_date' => '2026-10-20'], [
            ['product_id' => $a->id, 'quantity' => 10],
            ['product_id' => $b->id, 'quantity' => 4],
        ]);
        $this->stock->recordGoodsIn($user, ['supplier_id' => $supplier, 'transaction_date' => '2026-10-21'], [
            ['product_id' => $a->id, 'quantity' => 5],
        ]);

        $this->assertSame(15, $a->fresh()->stock);
        $this->assertSame(4, $b->fresh()->stock);
        $this->assertSame(TransactionStatus::Approved, $first->fresh()->status);
        $this->assertSame($user->id, $first->fresh()->approved_by);
        $this->assertSame(sprintf('IN-20261020-%05d', $first->id), $first->fresh()->code);
        $this->assertDatabaseHas('transaction_items', ['product_id' => $a->id, 'quantity' => 5, 'stock_before' => 10, 'stock_after' => 15]);
    }

    public function test_approving_twice_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $transaction = $this->stock->recordGoodsIn($user, ['supplier_id' => Transaction::factory()->make()->supplier_id, 'transaction_date' => now()->toDateString()], [
            ['product_id' => $product->id, 'quantity' => 3],
        ]);

        $this->expectException(StockException::class);
        try {
            $this->stock->approve($transaction, $user);
        } finally {
            $this->assertSame(3, $product->fresh()->stock);
        }
    }

    public function test_out_with_insufficient_stock_changes_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $enough = Product::factory()->create();
        $short = Product::factory()->create();
        Product::query()->whereKey($enough->id)->update(['stock' => 10]);
        Product::query()->whereKey($short->id)->update(['stock' => 1]);

        $out = Transaction::factory()->create(['type' => TransactionType::Out, 'supplier_id' => null, 'department_id' => Department::factory()]);
        $out->items()->create(['product_id' => $enough->id, 'quantity' => 4]);
        $out->items()->create(['product_id' => $short->id, 'quantity' => 2]);

        try {
            $this->stock->approve($out, $admin);
            $this->fail('Expected StockException');
        } catch (StockException $e) {
            $this->assertStringContainsString('tidak cukup', $e->getMessage());
        }

        // The whole approval rolled back, including the item that had enough stock.
        $this->assertSame(10, $enough->fresh()->stock);
        $this->assertSame(1, $short->fresh()->stock);
        $this->assertTrue($out->fresh()->isPending());
        $this->assertDatabaseMissing('transaction_items', ['transaction_id' => $out->id, 'stock_before' => 10]);
    }

    public function test_out_with_enough_stock_subtracts(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        Product::query()->whereKey($product->id)->update(['stock' => 10]);
        $out = Transaction::factory()->create(['type' => TransactionType::Out, 'supplier_id' => null, 'department_id' => Department::factory()]);
        $out->items()->create(['product_id' => $product->id, 'quantity' => 10]);

        $this->stock->approve($out, $admin);

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame($admin->id, $out->fresh()->approved_by);
    }

    public function test_trashed_product_blocks_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $transaction = Transaction::factory()->create();
        $transaction->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $product->delete();

        $this->expectException(StockException::class);
        $this->stock->approve($transaction, $admin);
    }
}
