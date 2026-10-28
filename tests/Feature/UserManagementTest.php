<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get('/users')->assertForbidden();
        $this->actingAs($staff)->post('/users', [])->assertForbidden();
    }

    public function test_admin_can_list_and_search_users(): void
    {
        User::factory()->create(['name' => 'Rina Wati']);
        User::factory()->create(['name' => 'Joko Susilo']);

        $this->actingAs($this->admin)->get('/users?q=rina')
            ->assertOk()
            ->assertSee('Rina Wati')
            ->assertDontSee('Joko Susilo');
    }

    public function test_admin_can_register_a_new_user_with_role(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
            'role' => 'admin',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/users');

        $user = User::where('email', 'dewi@example.com')->firstOrFail();
        $this->assertSame(Role::Admin, $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_create_validates_input(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => '',
            'email' => $this->admin->email,
            'role' => 'superuser',
            'password' => 'abc',
        ])->assertSessionHasErrors(['name', 'email', 'role', 'password']);
    }

    public function test_update_keeps_password_when_left_empty(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->password;

        $this->actingAs($this->admin)->put("/users/{$user->id}", [
            'name' => 'Nama Baru',
            'email' => $user->email,
            'role' => 'staff',
            'password' => '',
        ])->assertRedirect('/users');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame($oldHash, $user->password);
    }

    public function test_admin_cannot_change_own_role_or_delete_self(): void
    {
        $this->actingAs($this->admin)->put("/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'staff',
        ])->assertSessionHas('error');
        $this->assertSame(Role::Admin, $this->admin->fresh()->role);

        $this->actingAs($this->admin)->delete("/users/{$this->admin->id}")->assertSessionHas('error');
        $this->assertModelExists($this->admin);
    }

    public function test_user_with_transactions_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->create(['created_by' => $user->id]);

        $this->actingAs($this->admin)->delete("/users/{$user->id}")->assertSessionHas('error');
        $this->assertModelExists($user);
    }

    public function test_user_with_activity_log_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        ActivityLog::create(['user_id' => $user->id, 'action' => 'login', 'description' => 'Masuk']);

        $this->actingAs($this->admin)->delete("/users/{$user->id}")->assertSessionHas('error');
        $this->assertModelExists($user);
    }

    public function test_search_treats_wildcards_literally(): void
    {
        User::factory()->create(['name' => 'Rina Wati']);

        $this->actingAs($this->admin)->get('/users?q=%25')->assertOk()->assertDontSee('Rina Wati');
    }

    public function test_admin_can_delete_user_without_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->delete("/users/{$user->id}")->assertRedirect('/users');
        $this->assertModelMissing($user);
    }
}
