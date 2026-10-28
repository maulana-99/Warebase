<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $admin;

    private Product $product;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create(['name' => 'Andi Staf']);
        $this->admin = User::factory()->admin()->create(['name' => 'Budi Admin']);
        $this->product = Product::factory()->create(['name' => 'Kertas A4']);
        $this->stock = app(StockService::class);
    }

    private function goodsIn(int $quantity): void
    {
        $this->stock->recordGoodsIn($this->staff, [
            'supplier_id' => Supplier::factory()->create()->id,
            'transaction_date' => now()->toDateString(),
        ], [['product_id' => $this->product->id, 'quantity' => $quantity]]);
    }

    private function goodsOut(int $quantity): void
    {
        $request = $this->stock->requestGoodsOut($this->staff, [
            'department_id' => Department::factory()->create()->id,
            'recipient_name' => 'Bu Sari',
            'transaction_date' => now()->toDateString(),
        ], [['product_id' => $this->product->id, 'quantity' => $quantity]]);
        $this->stock->approveRequest($request, $this->admin);
    }

    /** Movements: 20 in before the period, then in the period +10, -6, adjustment to 21 (-3). */
    private function seedMovements(): void
    {
        $this->travelTo('2026-09-15 10:00');
        $this->goodsIn(20);
        $this->travelTo('2026-10-05 10:00');
        $this->goodsIn(10);
        $this->travelTo('2026-10-06 10:00');
        $this->goodsOut(6);
        $this->travelTo('2026-10-07 10:00');
        $this->stock->adjustStock($this->admin, ['note' => 'Opname'], [['product_id' => $this->product->id, 'counted' => 21]], now()->subMinute());
        $this->travelTo('2026-10-28 10:00');
    }

    public function test_stock_movement_report_balances(): void
    {
        $this->seedMovements();

        $products = $this->actingAs($this->staff)->get('/reports?from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->viewData('products');

        $row = $products->firstWhere('id', $this->product->id);
        $this->assertSame([20, 10, 6, -3, 21], [$row->opening, $row->qty_in, $row->qty_out, $row->qty_adjustment, $row->closing]);
        $this->assertSame($row->closing, $row->opening + $row->qty_in - $row->qty_out + $row->qty_adjustment);
    }

    public function test_period_before_any_movement_shows_zero(): void
    {
        $this->seedMovements();

        $row = $this->actingAs($this->admin)->get('/reports?from=2026-08-01&to=2026-08-31')->viewData('products')->first();

        $this->assertSame([0, 0, 0, 0, 0], [$row->opening, $row->qty_in, $row->qty_out, $row->qty_adjustment, $row->closing]);
    }

    public function test_category_filter_and_date_validation(): void
    {
        $other = Product::factory()->create(['name' => 'Tinta']);

        $this->actingAs($this->admin)->get("/reports?category={$other->category_id}")->assertSee('Tinta')->assertDontSee('Kertas A4');
        $this->actingAs($this->admin)->get('/reports?from=2026-10-10&to=2026-10-01')->assertSessionHasErrors('to');
        $this->actingAs($this->admin)->get('/reports?from='.now()->addMonth()->toDateString())->assertSessionHasErrors('to');
        $this->actingAs($this->admin)->get('/reports?to=2026-10-20')->assertOk();
    }

    public function test_work_trail_counts_and_staff_sees_only_own_row(): void
    {
        $this->seedMovements();

        $users = $this->actingAs($this->admin)->get('/reports?tab=staff&from=2026-10-01&to=2026-10-31')->viewData('users');
        $staffRow = $users->firstWhere('id', $this->staff->id);
        $adminRow = $users->firstWhere('id', $this->admin->id);
        $this->assertSame([1, 1], [$staffRow->goods_in_count, $staffRow->requests_count]);
        $this->assertSame([1, 0, 1], [$adminRow->approved_count, $adminRow->rejected_count, $adminRow->adjustments_count]);

        $this->actingAs($this->staff)->get('/reports?tab=staff')->assertSee('Andi Staf')->assertDontSee('Budi Admin');
    }

    public function test_csv_export_neutralises_formulas(): void
    {
        Product::query()->whereKey($this->product->id)->update(['name' => '=HYPERLINK("http://evil")']);

        $response = $this->actingAs($this->staff)->get('/reports/stock.csv?from=2026-10-01&to=2026-10-31');

        $response->assertOk()->assertHeader('Content-Disposition', 'attachment; filename=laporan-stok-2026-10-01-2026-10-31.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('SKU,Produk', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/reports/stock.csv')->assertRedirect('/login');
    }
}
