<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'username' => 'vendor-aktif',
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_pending_vendor_is_not_authenticated(): void
    {
        $user = User::factory()->create([
            'username' => 'vendor-menunggu',
            'role' => 'vendor',
            'status' => 'pending',
        ]);

        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertSessionHasErrors([
            'username' => 'Akun Anda sedang ditinjau Admin',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
