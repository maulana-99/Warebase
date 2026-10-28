<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCardTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $this->product = Product::factory()->create(['name' => 'Kertas A4']);
    }

    private function goodsIn(int $quantity): void
    {
        app(StockService::class)->recordGoodsIn($this->staff, [
            'supplier_id' => Supplier::factory()->create()->id,
            'transaction_date' => now()->toDateString(),
        ], [['product_id' => $this->product->id, 'quantity' => $quantity]]);
    }

    public function test_shows_running_balance_in_approval_order(): void
    {
        $this->goodsIn(10);
        $this->travel(1)->minutes();
        $request = app(StockService::class)->requestGoodsOut($this->staff, [
            'department_id' => Department::factory()->create()->id,
            'recipient_name' => 'Bu Sari',
            'transaction_date' => now()->toDateString(),
        ], [['product_id' => $this->product->id, 'quantity' => 4]]);
        app(StockService::class)->approveRequest($request, $this->admin);
        $this->travel(1)->minutes();
        $this->goodsIn(3);

        $this->actingAs($this->staff)->get("/products/{$this->product->id}/stock-card")
            ->assertOk()
            ->assertSeeInOrder(['+10', '10', '−4', '6', '+3', '9'])
            ->assertSee('cocok dengan stok sistem');
    }

    public function test_pending_requests_are_not_movements(): void
    {
        $this->goodsIn(5);
        $pending = app(StockService::class)->requestGoodsOut($this->staff, [
            'department_id' => Department::factory()->create()->id,
            'recipient_name' => 'Pak Budi',
            'transaction_date' => now()->toDateString(),
        ], [['product_id' => $this->product->id, 'quantity' => 2]]);

        $this->actingAs($this->admin)->get("/products/{$this->product->id}/stock-card")->assertDontSee($pending->code);
    }

    public function test_flags_stock_changed_outside_transactions(): void
    {
        $this->goodsIn(5);
        Product::query()->whereKey($this->product->id)->update(['stock' => 50]);

        $this->actingAs($this->admin)->get("/products/{$this->product->id}/stock-card")
            ->assertSee('berbeda dengan stok sistem');
    }

    public function test_date_filter_and_validation(): void
    {
        $this->travelTo(now()->subDays(3));
        $this->goodsIn(7);
        $this->travelBack();
        $this->goodsIn(2);

        $this->actingAs($this->admin)->get("/products/{$this->product->id}/stock-card?from=".now()->toDateString())
            ->assertSee('+2')->assertDontSee('+7');
        $this->actingAs($this->admin)->get("/products/{$this->product->id}/stock-card?from=2026-10-10&to=2026-10-01")
            ->assertSessionHasErrors('to');
    }

    public function test_staff_gets_no_link_to_other_peoples_requests(): void
    {
        $this->goodsIn(10);
        $other = User::factory()->create();
        $request = app(StockService::class)->requestGoodsOut($other, [
            'department_id' => Department::factory()->create()->id,
            'recipient_name' => 'X',
            'transaction_date' => now()->toDateString(),
        ], [['product_id' => $this->product->id, 'quantity' => 1]]);
        app(StockService::class)->approveRequest($request, $this->admin);

        $this->actingAs($this->staff)->get("/products/{$this->product->id}/stock-card")
            ->assertSee($request->code)
            ->assertDontSee(route('requests.show', $request));
        $this->actingAs($this->admin)->get("/products/{$this->product->id}/stock-card")
            ->assertSee(route('requests.show', $request));
    }

    public function test_staff_does_not_see_adjustment_reason(): void
    {
        $this->goodsIn(10);
        app(StockService::class)->adjustStock($this->admin, ['note' => 'Rahasia: dicuri staf gudang'], [
            ['product_id' => $this->product->id, 'counted' => 8],
        ], now()->addSecond());

        $this->actingAs($this->staff)->get("/products/{$this->product->id}/stock-card")->assertDontSee('Rahasia');
        $this->actingAs($this->admin)->get("/products/{$this->product->id}/stock-card")->assertSee('Rahasia');
    }
}
