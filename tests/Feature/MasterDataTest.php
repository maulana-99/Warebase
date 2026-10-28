<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /** @return array<string, array{string, class-string, array<string, string>}> */
    public static function types(): array
    {
        return [
            'categories' => ['categories', Category::class, ['name' => 'Alat Tulis']],
            'locations' => ['locations', Location::class, ['code' => 'z-09', 'name' => 'Rak Z']],
            'suppliers' => ['suppliers', Supplier::class, ['name' => 'CV Sinar Jaya', 'phone' => '0812', 'email' => 'cv@sinar.test']],
            'departments' => ['departments', Department::class, ['name' => 'Humas']],
        ];
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/master/categories')->assertRedirect('/login');
    }

    public function test_master_index_redirects_to_categories(): void
    {
        $this->actingAs($this->admin)->get('/master')->assertRedirect('/master/categories');
    }

    public function test_unknown_type_is_not_found(): void
    {
        $this->actingAs($this->admin)->get('/master/users')->assertNotFound();
    }

    #[DataProvider('types')]
    public function test_staff_can_view_but_not_change(string $type, string $model): void
    {
        $staff = User::factory()->create();
        $item = $model::factory()->create();

        $this->actingAs($staff)->get("/master/{$type}")->assertOk()->assertDontSee('Aksi');
        $this->actingAs($staff)->get("/master/{$type}/create")->assertForbidden();
        $this->actingAs($staff)->post("/master/{$type}", [])->assertForbidden();
        $this->actingAs($staff)->get("/master/{$type}/{$item->id}/edit")->assertForbidden();
        $this->actingAs($staff)->put("/master/{$type}/{$item->id}", [])->assertForbidden();
        $this->actingAs($staff)->delete("/master/{$type}/{$item->id}")->assertForbidden();
        $this->assertModelExists($item);
    }

    #[DataProvider('types')]
    public function test_admin_can_create(string $type, string $model, array $data): void
    {
        $this->actingAs($this->admin)->get("/master/{$type}/create")->assertOk();

        $this->actingAs($this->admin)->post("/master/{$type}", $data)
            ->assertRedirect("/master/{$type}")
            ->assertSessionHas('success');

        $this->assertSame(1, $model::count());
    }

    #[DataProvider('types')]
    public function test_admin_can_update(string $type, string $model, array $data): void
    {
        $item = $model::factory()->create();

        $this->actingAs($this->admin)->get("/master/{$type}/{$item->id}/edit")->assertOk();
        $this->actingAs($this->admin)->put("/master/{$type}/{$item->id}", $data)
            ->assertRedirect("/master/{$type}");

        $this->assertSame($data['name'], $item->fresh()->name);
    }

    #[DataProvider('types')]
    public function test_name_is_required_and_has_max_length(string $type, string $model, array $data): void
    {
        $this->actingAs($this->admin)->post("/master/{$type}", array_merge($data, ['name' => '']))
            ->assertSessionHasErrors('name');
        $this->actingAs($this->admin)->post("/master/{$type}", array_merge($data, ['name' => str_repeat('a', 151)]))
            ->assertSessionHasErrors('name');
        $this->assertSame(0, $model::count());
    }

    public function test_unique_fields_reject_duplicates_but_allow_keeping_own_value(): void
    {
        $category = Category::factory()->create(['name' => 'ATK']);
        Location::factory()->create(['code' => 'A-01']);

        $this->actingAs($this->admin)->post('/master/categories', ['name' => 'ATK'])->assertSessionHasErrors('name');
        $this->actingAs($this->admin)->post('/master/locations', ['code' => 'a-01', 'name' => 'Rak'])->assertSessionHasErrors('code');
        $this->actingAs($this->admin)->put("/master/categories/{$category->id}", ['name' => 'ATK', 'description' => 'baru'])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame('baru', $category->fresh()->description);
    }

    public function test_location_code_is_stored_uppercase(): void
    {
        $this->actingAs($this->admin)->post('/master/locations', ['code' => ' b-02 ', 'name' => 'Rak B'])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('locations', ['code' => 'B-02']);
    }

    public function test_supplier_email_must_be_valid(): void
    {
        $this->actingAs($this->admin)->post('/master/suppliers', ['name' => 'PT X', 'email' => 'bukan-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_search_filters_and_treats_wildcards_literally(): void
    {
        Category::factory()->create(['name' => 'Kertas']);
        Category::factory()->create(['name' => 'Tinta']);

        $this->actingAs($this->admin)->get('/master/categories?q=kert')->assertSee('Kertas')->assertDontSee('Tinta');
        $this->actingAs($this->admin)->get('/master/categories?q=%25')->assertDontSee('Kertas')->assertDontSee('Tinta');
    }

    public function test_item_in_use_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        $transaction = Transaction::factory()->create();
        $department = Department::factory()->create();
        Transaction::factory()->create(['type' => TransactionType::Out, 'supplier_id' => null, 'department_id' => $department->id]);

        foreach ([
            ['categories', $product->category],
            ['locations', $product->location],
            ['suppliers', $transaction->supplier],
            ['departments', $department],
        ] as [$type, $item]) {
            $this->actingAs($this->admin)->delete("/master/{$type}/{$item->id}")
                ->assertRedirect()
                ->assertSessionHas('error');
            $this->assertModelExists($item);
        }
    }

    public function test_item_used_only_by_a_trashed_product_still_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->actingAs($this->admin)->delete("/master/categories/{$product->category_id}")->assertSessionHas('error');

        $this->assertModelExists($product->category);
    }

    public function test_unused_item_is_removed_and_its_name_can_be_reused(): void
    {
        $category = Category::factory()->create(['name' => 'Sementara']);

        $this->actingAs($this->admin)->delete("/master/categories/{$category->id}")
            ->assertRedirect('/master/categories')
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        $this->actingAs($this->admin)->post('/master/categories', ['name' => 'Sementara'])->assertSessionDoesntHaveErrors();
    }

    public function test_list_shows_usage_count(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->get('/master/categories')->assertSee($product->category->name)->assertSee('1 produk');
    }
}
