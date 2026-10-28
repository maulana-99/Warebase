<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_login_and_register_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
        $this->get('/register')->assertOk()->assertSee('Buat akun');
    }

    public function test_user_can_register_as_staff_and_cannot_choose_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/dashboard');
        $user = User::where('email', 'siti@example.com')->firstOrFail();
        $this->assertSame(Role::Staff, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_per_ip_across_emails(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 20) as $i) {
            $this->post('/login', ['email' => "nobody{$i}@example.com", 'password' => 'salah']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logged_in_user_cannot_open_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/dashboard');
    }

    public function test_logout_requires_csrf_protected_post(): void
    {
        $this->actingAs(User::factory()->create())->get('/logout')->assertMethodNotAllowed();
    }

    public function test_role_middleware_blocks_staff_and_allows_admin(): void
    {
        Route::middleware(['web', 'auth', 'role:admin'])->get('/_admin-only', fn () => 'ok');

        $this->actingAs(User::factory()->create())->get('/_admin-only')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/_admin-only')->assertOk();
    }
}
