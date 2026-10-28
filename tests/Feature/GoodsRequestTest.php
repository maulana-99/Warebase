<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Department;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsRequestTest extends TestCase
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
        $this->product = Product::factory()->create();
        Product::query()->whereKey($this->product->id)->update(['stock' => 20]);
    }

    /** @return array<string, mixed> */
    private function validData(array $override = []): array
    {
        return array_merge([
            'department_id' => Department::factory()->create()->id,
            'recipient_name' => 'Bu Sari',
            'transaction_date' => now()->toDateString(),
            'note' => 'Ujian',
            'items' => [['product_id' => $this->product->id, 'quantity' => 5]],
        ], $override);
    }

    private function pendingRequest(?User $by = null, int $quantity = 5): Transaction
    {
        return app(StockService::class)->requestGoodsOut($by ?? $this->staff, [
            'department_id' => Department::factory()->create()->id,
            'recipient_name' => 'Pak Budi',
            'transaction_date' => now()->toDateString(),
        ], [['product_id' => $this->product->id, 'quantity' => $quantity]]);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/requests')->assertRedirect('/login');
    }

    public function test_staff_files_a_request_and_stock_is_untouched(): void
    {
        $this->actingAs($this->staff)->get('/requests/create')->assertOk();
        $response = $this->actingAs($this->staff)->post('/requests', $this->validData());

        $transaction = Transaction::sole();
        $response->assertRedirect("/requests/{$transaction->id}")->assertSessionHas('success');
        $this->assertSame(TransactionType::Out, $transaction->type);
        $this->assertTrue($transaction->isPending());
        $this->assertStringStartsWith('OUT-', $transaction->code);
        $this->assertSame(20, $this->product->fresh()->stock);
    }

    public function test_request_above_current_stock_is_refused(): void
    {
        $this->actingAs($this->staff)
            ->post('/requests', $this->validData(['items' => [['product_id' => $this->product->id, 'quantity' => 21]]]))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
    }

    public function test_validation_rules(): void
    {
        foreach ([
            'department_id' => ['department_id' => 9999],
            'recipient_name' => ['recipient_name' => ''],
            'transaction_date' => ['transaction_date' => now()->addDay()->toDateString()],
            'items' => ['items' => []],
            'items.0.quantity' => ['items' => [['product_id' => $this->product->id, 'quantity' => 0]]],
        ] as $field => $override) {
            $this->actingAs($this->staff)->post('/requests', $this->validData($override))->assertSessionHasErrors($field);
        }

        $this->assertSame(0, Transaction::count());
    }

    public function test_staff_only_sees_own_requests(): void
    {
        $mine = $this->pendingRequest();
        $other = $this->pendingRequest(User::factory()->create());

        $this->actingAs($this->staff)->get('/requests')->assertSee($mine->code)->assertDontSee($other->code);
        $this->actingAs($this->staff)->get("/requests/{$mine->id}")->assertOk();
        $this->actingAs($this->staff)->get("/requests/{$other->id}")->assertNotFound();
        $this->actingAs($this->admin)->get('/requests')->assertSee($mine->code)->assertSee($other->code);
    }

    public function test_status_filter(): void
    {
        $pending = $this->pendingRequest();
        $rejected = $this->pendingRequest();
        app(StockService::class)->rejectRequest($rejected, $this->admin, 'Tidak perlu');

        $this->actingAs($this->admin)->get('/requests?status=pending')->assertSee($pending->code)->assertDontSee($rejected->code);
        $this->actingAs($this->admin)->get('/requests?status=bogus')->assertOk()->assertSee($pending->code)->assertSee($rejected->code);
    }

    public function test_staff_cannot_approve_or_reject(): void
    {
        $transaction = $this->pendingRequest(User::factory()->create());

        $this->actingAs($this->staff)->post("/requests/{$transaction->id}/approve")->assertForbidden();
        $this->actingAs($this->staff)->post("/requests/{$transaction->id}/reject", ['reject_reason' => 'x'])->assertForbidden();
        $this->assertTrue($transaction->fresh()->isPending());
    }

    public function test_admin_approves_and_stock_goes_down(): void
    {
        $transaction = $this->pendingRequest();

        $this->actingAs($this->admin)->get("/requests/{$transaction->id}")->assertSee('Setujui');
        $this->actingAs($this->admin)->post("/requests/{$transaction->id}/approve")->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame(TransactionStatus::Approved, $transaction->status);
        $this->assertSame($this->admin->id, $transaction->approved_by);
        $this->assertSame(15, $this->product->fresh()->stock);
        $this->assertDatabaseHas('transaction_items', ['transaction_id' => $transaction->id, 'stock_before' => 20, 'stock_after' => 15]);
    }

    public function test_admin_cannot_decide_own_request(): void
    {
        $transaction = $this->pendingRequest($this->admin);

        $this->actingAs($this->admin)->get("/requests/{$transaction->id}")->assertDontSee('Setujui')->assertSee('Admin lain');
        $this->actingAs($this->admin)->post("/requests/{$transaction->id}/approve")->assertSessionHas('error');
        $this->actingAs($this->admin)->post("/requests/{$transaction->id}/reject", ['reject_reason' => 'x'])->assertSessionHas('error');

        $this->assertTrue($transaction->fresh()->isPending());
        $this->assertSame(20, $this->product->fresh()->stock);
    }

    public function test_reject_requires_reason_and_keeps_stock(): void
    {
        $transaction = $this->pendingRequest();

        $this->actingAs($this->admin)->post("/requests/{$transaction->id}/reject", ['reject_reason' => ''])->assertSessionHasErrors('reject_reason');
        $this->assertTrue($transaction->fresh()->isPending());

        $this->actingAs($this->admin)->post("/requests/{$transaction->id}/reject", ['reject_reason' => 'Stok untuk ujian'])->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame(TransactionStatus::Rejected, $transaction->status);
        $this->assertSame('Stok untuk ujian', $transaction->reject_reason);
        $this->assertSame(20, $this->product->fresh()->stock);
        $this->actingAs($this->staff)->get("/requests/{$transaction->id}")->assertSee('Stok untuk ujian');
    }

    public function test_decided_request_cannot_be_decided_again(): void
    {
        $transaction = $this->pendingRequest();
        $this->actingAs($this->admin)->post("/requests/{$transaction->id}/approve");

        $this->actingAs($this->admin)->post("/requests/{$transaction->id}/approve")->assertSessionHas('error');
        $this->actingAs($this->admin)->post("/requests/{$transaction->id}/reject", ['reject_reason' => 'x'])->assertSessionHas('error');

        $this->assertSame(15, $this->product->fresh()->stock);
        $this->assertSame(TransactionStatus::Approved, $transaction->fresh()->status);
    }

    public function test_approval_fails_cleanly_when_stock_ran_out_meanwhile(): void
    {
        $first = $this->pendingRequest(quantity: 15);
        $second = $this->pendingRequest(quantity: 10);

        $this->actingAs($this->admin)->post("/requests/{$first->id}/approve")->assertSessionHas('success');
        $this->actingAs($this->admin)->post("/requests/{$second->id}/approve")->assertSessionHas('error');

        $this->assertSame(5, $this->product->fresh()->stock);
        $this->assertTrue($second->fresh()->isPending());
    }

    public function test_goods_in_cannot_be_approved_through_requests(): void
    {
        $goodsIn = Transaction::factory()->create(['created_by' => $this->staff->id]);

        $this->actingAs($this->admin)->get("/requests/{$goodsIn->id}")->assertNotFound();
        $this->actingAs($this->admin)->post("/requests/{$goodsIn->id}/approve")->assertSessionHas('error');
        $this->assertTrue($goodsIn->fresh()->isPending());
    }
}
