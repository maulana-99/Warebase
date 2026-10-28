<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['email' => 'admin@test.id']);
    }

    public function test_login_logout_and_failed_login_are_logged(): void
    {
        $this->post('/login', ['email' => 'admin@test.id', 'password' => 'salah']);
        $this->post('/login', ['email' => 'admin@test.id', 'password' => 'password']);
        $this->post('/logout');

        $this->assertSame(['auth.failed', 'auth.login', 'auth.logout'], ActivityLog::orderBy('id')->pluck('action')->all());
        $failed = ActivityLog::where('action', 'auth.failed')->sole();
        $this->assertNull($failed->user_id);
        $this->assertStringContainsString('admin@test.id', $failed->description);
        $this->assertSame('127.0.0.1', $failed->ip_address);
    }

    public function test_update_records_old_and_new_values_without_secrets(): void
    {
        $product = Product::factory()->create(['name' => 'Lama', 'sku' => 'P-1']);
        $this->actingAs($this->admin)->put("/products/{$product->id}", [
            'category_id' => $product->category_id, 'location_id' => null, 'sku' => 'P-1',
            'name' => 'Baru', 'unit' => $product->unit, 'min_stock' => $product->min_stock,
        ]);
        $staff = User::factory()->create();
        $this->actingAs($this->admin)->put("/users/{$staff->id}", [
            'name' => $staff->name, 'email' => $staff->email, 'role' => 'staff',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ]);

        $productLog = ActivityLog::where('action', 'product.updated')->sole();
        $this->assertEquals(['old' => 'Lama', 'new' => 'Baru'], $productLog->properties['changes']['name']);
        $this->assertSame($product->id, $productLog->subject_id);

        $userLog = ActivityLog::where('action', 'user.updated')->sole();
        $this->assertEquals(['old' => '***', 'new' => '***'], $userLog->properties['changes']['password']);
        $this->assertStringNotContainsString('rahasia123', json_encode($userLog->properties));
    }

    public function test_crud_actions_are_logged(): void
    {
        $this->actingAs($this->admin)->post('/master/categories', ['name' => 'ATK']);
        $category = Category::sole();
        $this->actingAs($this->admin)->delete("/master/categories/{$category->id}");

        $this->assertSame(['master.created', 'master.deleted'], ActivityLog::orderBy('id')->pluck('action')->all());
    }

    public function test_entries_are_append_only(): void
    {
        $log = ActivityLog::create(['action' => 'x', 'description' => 'y']);

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_only_admin_sees_the_log_and_filters_work(): void
    {
        $staff = User::factory()->create(['name' => 'Rina']);
        ActivityLog::create(['user_id' => $staff->id, 'action' => 'goods_in.recorded', 'description' => 'Mencatat barang masuk IN-1']);
        ActivityLog::create(['user_id' => $this->admin->id, 'action' => 'auth.login', 'description' => 'Masuk ke aplikasi']);

        $this->actingAs($staff)->get('/activity-logs')->assertForbidden();
        $this->actingAs($this->admin)->get('/activity-logs?group=goods_in')->assertSee('IN-1')->assertDontSee('Masuk ke aplikasi');
        $this->actingAs($this->admin)->get("/activity-logs?user={$this->admin->id}")->assertSee('Masuk ke aplikasi')->assertDontSee('IN-1');
        $this->actingAs($this->admin)->get('/activity-logs?group=bogus')->assertOk()->assertSee('IN-1');
    }

    public function test_long_descriptions_fit_and_log_failures_never_break_the_action(): void
    {
        app(\App\Services\ActivityLogger::class)->log('x.long', str_repeat('a', 400));
        $this->assertLessThanOrEqual(255, mb_strlen(ActivityLog::where('action', 'x.long')->sole()->description));

        \Illuminate\Support\Facades\Schema::rename('activity_logs', 'activity_logs_off');
        try {
            $this->actingAs($this->admin)->post('/master/categories', ['name' => 'Tetap Tersimpan'])->assertRedirect('/master/categories');
        } finally {
            \Illuminate\Support\Facades\Schema::rename('activity_logs_off', 'activity_logs');
        }
        $this->assertDatabaseHas('categories', ['name' => 'Tetap Tersimpan']);
    }
}
