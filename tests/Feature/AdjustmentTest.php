<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\StockException;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $kertas;

    private Product $tinta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->kertas = Product::factory()->create(['name' => 'Kertas']);
        $this->tinta = Product::factory()->create(['name' => 'Tinta']);
        Product::query()->whereKey([$this->kertas->id, $this->tinta->id])->update(['stock' => 10]);
    }

    /** @return array<string, mixed> */
    private function validData(array $items, array $override = []): array
    {
        return array_merge([
            'counted_since' => now()->subMinute()->format('Y-m-d H:i:s'),
            'note' => 'Stock opname bulanan',
            'items' => $items,
        ], $override);
    }

    public function test_staff_cannot_adjust(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get('/adjustments')->assertForbidden();
        $this->actingAs($staff)->get('/adjustments/create')->assertForbidden();
        $this->actingAs($staff)->post('/adjustments', $this->validData([['product_id' => $this->kertas->id, 'counted' => 0]]))->assertForbidden();
        $this->assertSame(10, $this->kertas->fresh()->stock);
    }

    public function test_service_refuses_non_admin(): void
    {
        $this->expectException(StockException::class);

        app(StockService::class)->adjustStock(User::factory()->create(), ['note' => 'x'], [
            ['product_id' => $this->kertas->id, 'counted' => 0],
        ], now());
    }

    public function test_admin_adjusts_up_and_down_and_skips_unchanged(): void
    {
        $unchanged = Product::factory()->create();
        Product::query()->whereKey($unchanged->id)->update(['stock' => 4]);

        $this->actingAs($this->admin)->get('/adjustments/create')->assertOk();
        $response = $this->actingAs($this->admin)->post('/adjustments', $this->validData([
            ['product_id' => $this->kertas->id, 'counted' => '7'],
            ['product_id' => $this->tinta->id, 'counted' => '13'],
            ['product_id' => $unchanged->id, 'counted' => '4'],
        ]));

        $transaction = Transaction::sole();
        $response->assertRedirect("/adjustments/{$transaction->id}")->assertSessionHas('success');
        $this->assertSame(TransactionType::Adjustment, $transaction->type);
        $this->assertSame(TransactionStatus::Approved, $transaction->status);
        $this->assertStringStartsWith('ADJ-', $transaction->code);
        $this->assertSame(7, $this->kertas->fresh()->stock);
        $this->assertSame(13, $this->tinta->fresh()->stock);
        $this->assertSame(2, $transaction->items()->count());
        $this->assertDatabaseHas('transaction_items', ['product_id' => $this->kertas->id, 'quantity' => 3, 'stock_before' => 10, 'stock_after' => 7]);
        $this->assertDatabaseHas('transaction_items', ['product_id' => $this->tinta->id, 'quantity' => 3, 'stock_before' => 10, 'stock_after' => 13]);

        $this->actingAs($this->admin)->get("/adjustments/{$transaction->id}")->assertOk()->assertSee('+3')->assertSee('−3');
    }

    public function test_adjust_to_zero_is_allowed(): void
    {
        $this->actingAs($this->admin)->post('/adjustments', $this->validData([['product_id' => $this->kertas->id, 'counted' => 0]]))
            ->assertSessionHas('success');

        $this->assertSame(0, $this->kertas->fresh()->stock);
    }

    public function test_nothing_to_adjust_is_refused(): void
    {
        $this->actingAs($this->admin)->post('/adjustments', $this->validData([['product_id' => $this->kertas->id, 'counted' => 10]]))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Transaction::count());
    }

    public function test_validation_rules(): void
    {
        foreach ([
            'note' => [[['product_id' => $this->kertas->id, 'counted' => 1]], ['note' => '']],
            'items.0.counted' => [[['product_id' => $this->kertas->id, 'counted' => -1]], []],
            'items.1.product_id' => [[['product_id' => $this->kertas->id, 'counted' => 1], ['product_id' => $this->kertas->id, 'counted' => 2]], []],
            'counted_since' => [[['product_id' => $this->kertas->id, 'counted' => 1]], ['counted_since' => now()->addHour()->format('Y-m-d H:i:s')]],
        ] as $field => [$items, $override]) {
            $this->actingAs($this->admin)->post('/adjustments', $this->validData($items, $override))->assertSessionHasErrors($field);
        }

        $this->assertSame(0, Transaction::count());
        $this->assertSame(10, $this->kertas->fresh()->stock);
    }

    public function test_index_lists_adjustments_and_other_types_are_not_shown(): void
    {
        $this->actingAs($this->admin)->post('/adjustments', $this->validData([['product_id' => $this->kertas->id, 'counted' => 1]]));
        $adjustment = Transaction::sole();
        $goodsIn = Transaction::factory()->create();

        $this->actingAs($this->admin)->get('/adjustments')->assertSee($adjustment->code)->assertDontSee($goodsIn->code);
        $this->actingAs($this->admin)->get("/adjustments/{$goodsIn->id}")->assertNotFound();
    }

    public function test_stale_count_is_refused_so_later_movements_are_not_erased(): void
    {
        $formOpenedAt = now()->subMinutes(10);
        $this->travel(-5)->minutes();
        app(StockService::class)->recordGoodsIn(User::factory()->create(), [
            'supplier_id' => Transaction::factory()->make()->supplier_id,
            'transaction_date' => now()->toDateString(),
        ], [['product_id' => $this->kertas->id, 'quantity' => 20]]);
        $this->travelBack();

        $this->actingAs($this->admin)
            ->post('/adjustments', $this->validData([
                ['product_id' => $this->kertas->id, 'counted' => 7],
                ['product_id' => $this->tinta->id, 'counted' => 3],
            ], ['counted_since' => $formOpenedAt->format('Y-m-d H:i:s')]))
            ->assertSessionHasErrors('items');

        $this->assertSame(30, $this->kertas->fresh()->stock);
        $this->assertSame(10, $this->tinta->fresh()->stock);
        $this->assertSame(0, Transaction::where('type', TransactionType::Adjustment)->count());
    }

    public function test_dashboard_shows_product_count_for_adjustments(): void
    {
        $this->actingAs($this->admin)->post('/adjustments', $this->validData([
            ['product_id' => $this->kertas->id, 'counted' => 7],
            ['product_id' => $this->tinta->id, 'counted' => 13],
        ]));

        $this->actingAs($this->admin)->get('/dashboard')->assertSee('2 produk');
    }
}
