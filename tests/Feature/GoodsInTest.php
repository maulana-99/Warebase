<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Department;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsInTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create();
    }

    /** @return array<string, mixed> */
    private function validData(array $override = []): array
    {
        return array_merge([
            'supplier_id' => Supplier::factory()->create()->id,
            'transaction_date' => now()->toDateString(),
            'note' => 'Surat jalan 123',
            'items' => [
                ['product_id' => Product::factory()->create()->id, 'quantity' => 10],
                ['product_id' => Product::factory()->create()->id, 'quantity' => 2],
            ],
        ], $override);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/goods-in')->assertRedirect('/login');
        $this->post('/goods-in', [])->assertRedirect('/login');
    }

    public function test_staff_can_record_goods_in_and_stock_increases(): void
    {
        $data = $this->validData();

        $this->actingAs($this->staff)->get('/goods-in/create')->assertOk();
        $response = $this->actingAs($this->staff)->post('/goods-in', $data);

        $transaction = Transaction::sole();
        $response->assertRedirect("/goods-in/{$transaction->id}")->assertSessionHas('success');
        $this->assertSame(TransactionType::In, $transaction->type);
        $this->assertSame($this->staff->id, $transaction->created_by);
        $this->assertSame(10, Product::find($data['items'][0]['product_id'])->stock);
        $this->assertSame(2, Product::find($data['items'][1]['product_id'])->stock);
    }

    public function test_client_cannot_set_status_or_creator(): void
    {
        $other = User::factory()->admin()->create();

        $this->actingAs($this->staff)->post('/goods-in', $this->validData(['status' => 'rejected', 'created_by' => $other->id, 'code' => 'HACK']));

        $transaction = Transaction::sole();
        $this->assertSame($this->staff->id, $transaction->created_by);
        $this->assertStringStartsWith('IN-', $transaction->code);
    }

    public function test_validation_rules(): void
    {
        $product = Product::factory()->create();
        $trashed = Product::factory()->create();
        $trashed->delete();

        $cases = [
            'supplier_id' => ['supplier_id' => 9999],
            'transaction_date' => ['transaction_date' => now()->addDay()->toDateString()],
            'items' => ['items' => []],
            'items.0.quantity' => ['items' => [['product_id' => $product->id, 'quantity' => 0]]],
            'items.1.product_id' => ['items' => [['product_id' => $product->id, 'quantity' => 1], ['product_id' => $product->id, 'quantity' => 1]]],
            'items.0.product_id' => ['items' => [['product_id' => $trashed->id, 'quantity' => 1]]],
        ];

        foreach ($cases as $field => $override) {
            $this->actingAs($this->staff)->post('/goods-in', $this->validData($override))->assertSessionHasErrors($field);
        }

        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_list_search_and_detail(): void
    {
        $this->actingAs($this->staff)->post('/goods-in', $this->validData(['supplier_id' => Supplier::factory()->create(['name' => 'CV Maju'])->id]));
        $this->actingAs($this->staff)->post('/goods-in', $this->validData(['supplier_id' => Supplier::factory()->create(['name' => 'PT Lain'])->id]));
        $transaction = Transaction::whereHas('supplier', fn ($q) => $q->where('name', 'CV Maju'))->sole();

        $this->actingAs($this->staff)->get('/goods-in?q=maju')->assertOk()->assertSee('CV Maju')->assertDontSee('PT Lain');
        $this->actingAs($this->staff)->get("/goods-in?q={$transaction->code}")->assertSee($transaction->code);
        $this->actingAs($this->staff)->get("/goods-in/{$transaction->id}")->assertOk()->assertSee($transaction->code)->assertSee('Stok sesudah');
    }

    public function test_detail_of_other_transaction_types_is_not_found(): void
    {
        $out = Transaction::factory()->create(['type' => TransactionType::Out, 'supplier_id' => null, 'department_id' => Department::factory()]);

        $this->actingAs($this->staff)->get("/goods-in/{$out->id}")->assertNotFound();
    }

    public function test_crafted_array_in_old_input_does_not_break_the_form(): void
    {
        $this->actingAs($this->staff)
            ->from('/goods-in/create')
            ->post('/goods-in', ['items' => [['product_id' => ['x'], 'quantity' => ['y']]]])
            ->assertRedirect('/goods-in/create');

        $this->actingAs($this->staff)->get('/goods-in/create')->assertOk();
    }
}
