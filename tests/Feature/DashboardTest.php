<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Department;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_seeded_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@warebase.test')->first();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Halo, Budi!')
            ->assertSee('Produk per Kategori')
            ->assertSee('Pulpen Hitam'); // every seeded product starts at 0 stock, so it is low stock
    }

    public function test_dashboard_counts_pending_requests_and_escapes_output(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);
        $transaction = Transaction::factory()->create([
            'type' => TransactionType::Out,
            'status' => TransactionStatus::Pending,
            'supplier_id' => null,
            'department_id' => Department::factory(),
            'created_by' => $user->id,
        ]);
        TransactionItem::factory()->for($transaction)->for(Product::factory())->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee($transaction->code)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
