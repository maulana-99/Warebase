<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_demo_accounts_and_stock_only_through_transactions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(Role::Admin, User::where('email', 'admin@warebase.test')->first()->role);
        $this->assertSame(Role::Staff, User::where('email', 'staff@warebase.test')->first()->role);
        $this->assertGreaterThan(0, Product::count());
        // Opening stock comes from goods-in transactions, so every product's stock equals its recorded movements.
        foreach (Product::all() as $product) {
            $this->assertSame($product->stock, (int) $product->transactionItems()->sum('quantity'));
        }
        $this->assertGreaterThan(0, Product::lowStock()->count());
    }

    public function test_transaction_relations_and_enum_casts(): void
    {
        $item = TransactionItem::factory()->create();
        $transaction = $item->transaction;

        $this->assertSame(TransactionType::In, $transaction->type);
        $this->assertSame(TransactionStatus::Pending, $transaction->status);
        $this->assertTrue($transaction->items->first()->is($item));
        $this->assertTrue($transaction->creator->createdTransactions->first()->is($transaction));
        $this->assertNotNull($item->product->category);
    }

    public function test_stock_cannot_be_mass_assigned(): void
    {
        $attributes = Product::factory()->raw();
        $product = Product::create([...$attributes, 'stock' => 999]);

        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_low_stock_scope(): void
    {
        $low = Product::factory()->create(['min_stock' => 10]);
        $ok = Product::factory()->create(['min_stock' => 0]);
        $ok->forceFill(['stock' => 5])->save();

        $ids = Product::lowStock()->pluck('id');

        $this->assertTrue($ids->contains($low->id));
        $this->assertFalse($ids->contains($ok->id));
    }

    public function test_category_in_use_cannot_be_force_deleted(): void
    {
        $product = Product::factory()->create();

        $this->expectException(QueryException::class);

        Category::find($product->category_id)->forceDelete();
    }

    public function test_product_with_history_cannot_be_force_deleted(): void
    {
        $item = TransactionItem::factory()->create();

        $this->expectException(QueryException::class);

        $item->product->forceDelete();
    }

    public function test_item_quantity_must_be_positive(): void
    {
        $this->expectException(QueryException::class);

        TransactionItem::factory()->create(['quantity' => 0]);
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Penyusup',
            'email' => 'x@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $this->assertSame(Role::Staff, $user->fresh()->role);
    }

    public function test_transactions_cannot_be_deleted(): void
    {
        $transaction = Transaction::factory()->create();

        $this->expectException(\LogicException::class);

        $transaction->delete();
    }

    public function test_out_transaction_requires_department_not_supplier(): void
    {
        $this->expectException(QueryException::class);

        Transaction::factory()->create(['type' => TransactionType::Out]);
    }

    public function test_approved_transaction_requires_approver(): void
    {
        $this->expectException(QueryException::class);

        Transaction::factory()->create(['status' => TransactionStatus::Approved]);
    }

    public function test_invalid_transaction_type_is_rejected_by_database(): void
    {
        $this->expectException(QueryException::class);

        $transaction = Transaction::factory()->create();

        DB::table('transactions')->where('id', $transaction->id)->update(['type' => 'gift']);
    }

    public function test_seeding_twice_does_not_duplicate_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [User::count(), Product::count(), \App\Models\Transaction::count(), (int) Product::sum('stock')];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [User::count(), Product::count(), \App\Models\Transaction::count(), (int) Product::sum('stock')]);
    }
}
