<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /** @return array<string, mixed> */
    private function validData(array $override = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'location_id' => Location::factory()->create()->id,
            'sku' => 'atk-001',
            'name' => 'Kertas A4',
            'unit' => 'rim',
            'min_stock' => 5,
            'description' => null,
        ], $override);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/products')->assertRedirect('/login');
    }

    public function test_staff_can_view_but_not_change(): void
    {
        $staff = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($staff)->get('/products')->assertOk()->assertSee($product->name)->assertDontSee('Aksi');
        $this->actingAs($staff)->get('/products/create')->assertForbidden();
        $this->actingAs($staff)->post('/products', $this->validData())->assertForbidden();
        $this->actingAs($staff)->get("/products/{$product->id}/edit")->assertForbidden();
        $this->actingAs($staff)->put("/products/{$product->id}", $this->validData())->assertForbidden();
        $this->actingAs($staff)->delete("/products/{$product->id}")->assertForbidden();
        $this->assertModelExists($product);
    }

    public function test_admin_can_create_with_uppercase_sku_and_zero_stock(): void
    {
        $this->actingAs($this->admin)->get('/products/create')->assertOk();
        $this->actingAs($this->admin)->post('/products', $this->validData())
            ->assertRedirect('/products')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['sku' => 'ATK-001', 'stock' => 0]);
    }

    public function test_stock_cannot_be_set_through_the_form(): void
    {
        $this->actingAs($this->admin)->post('/products', $this->validData(['stock' => 999]));

        $this->assertDatabaseHas('products', ['sku' => 'ATK-001', 'stock' => 0]);

        $product = Product::first();
        $this->actingAs($this->admin)->put("/products/{$product->id}", $this->validData(['sku' => 'ATK-001', 'stock' => 999]));

        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_location_is_optional(): void
    {
        $this->actingAs($this->admin)->post('/products', $this->validData(['location_id' => null]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('products', ['sku' => 'ATK-001', 'location_id' => null]);
    }

    public function test_validation_rules(): void
    {
        $existing = Product::factory()->create(['sku' => 'DUP-1']);

        $this->actingAs($this->admin)->post('/products', $this->validData(['sku' => 'dup-1']))->assertSessionHasErrors('sku');
        $this->actingAs($this->admin)->post('/products', $this->validData(['name' => '']))->assertSessionHasErrors('name');
        $this->actingAs($this->admin)->post('/products', $this->validData(['unit' => '']))->assertSessionHasErrors('unit');
        $this->actingAs($this->admin)->post('/products', $this->validData(['min_stock' => -1]))->assertSessionHasErrors('min_stock');
        $this->actingAs($this->admin)->post('/products', $this->validData(['min_stock' => 'abc']))->assertSessionHasErrors('min_stock');
        $this->actingAs($this->admin)->post('/products', $this->validData(['category_id' => 9999]))->assertSessionHasErrors('category_id');
        $this->actingAs($this->admin)->post('/products', $this->validData(['location_id' => 9999]))->assertSessionHasErrors('location_id');
        $this->assertSame(1, Product::count());
        $this->assertModelExists($existing);
    }

    public function test_admin_can_update_and_keep_own_sku(): void
    {
        $product = Product::factory()->create(['sku' => 'KEEP-1']);

        $this->actingAs($this->admin)->get("/products/{$product->id}/edit")->assertOk();
        $this->actingAs($this->admin)->put("/products/{$product->id}", $this->validData(['sku' => 'keep-1', 'name' => 'Nama Baru']))
            ->assertRedirect('/products')
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('Nama Baru', $product->fresh()->name);
    }

    public function test_search_category_and_low_stock_filters(): void
    {
        $kertas = Product::factory()->create(['name' => 'Kertas A4', 'sku' => 'K-1', 'min_stock' => 5]);
        $tinta = Product::factory()->create(['name' => 'Tinta Hitam', 'sku' => 'T-1', 'min_stock' => 5]);
        Product::query()->whereKey($tinta->id)->update(['stock' => 50]);

        $this->actingAs($this->admin)->get('/products?q=kertas')->assertSee('Kertas A4')->assertDontSee('Tinta Hitam');
        $this->actingAs($this->admin)->get('/products?q=T-1')->assertSee('Tinta Hitam')->assertDontSee('Kertas A4');
        $this->actingAs($this->admin)->get("/products?category={$tinta->category_id}")->assertSee('Tinta Hitam')->assertDontSee('Kertas A4');
        $this->actingAs($this->admin)->get('/products?low=1')->assertSee('Kertas A4')->assertDontSee('Tinta Hitam');
        $this->actingAs($this->admin)->get('/products?q=%25')->assertDontSee('Kertas A4')->assertDontSee('Tinta Hitam');
        $this->assertModelExists($kertas);
    }

    public function test_product_with_stock_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        Product::query()->whereKey($product->id)->update(['stock' => 3]);

        $this->actingAs($this->admin)->delete("/products/{$product->id}")->assertSessionHas('error');

        $this->assertNotSoftDeleted($product);
    }

    public function test_product_with_history_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        $transaction = Transaction::factory()->create();
        TransactionItem::factory()->create(['transaction_id' => $transaction->id, 'product_id' => $product->id]);

        $this->actingAs($this->admin)->delete("/products/{$product->id}")->assertSessionHas('error');

        $this->assertModelExists($product);
    }

    public function test_unused_product_is_removed_and_sku_can_be_reused(): void
    {
        $product = Product::factory()->create(['sku' => 'TMP-1']);

        $this->actingAs($this->admin)->delete("/products/{$product->id}")
            ->assertRedirect('/products')
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        $this->actingAs($this->admin)->post('/products', $this->validData(['sku' => 'TMP-1']))->assertSessionDoesntHaveErrors();
    }
}
