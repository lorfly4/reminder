<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_default_categories_are_created(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Nadia',
            'email' => 'nadia@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email', 'nadia@example.com');
        $this->assertAuthenticated();
        $this->assertDatabaseCount('categories', 7);
        $this->assertDatabaseHas('categories', ['name' => 'Personal', 'user_id' => User::firstOrFail()->id]);
    }

    public function test_user_can_login_logout_and_fetch_profile(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com']);

        $this->postJson('/api/v1/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ])->assertOk();

        $this->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'login@example.com');

        $this->postJson('/api/v1/logout')
            ->assertOk();
        $this->assertGuest('web');
    }

    public function test_profile_and_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/user/profile', [
            'name' => 'Updated Name',
            'timezone' => 'Asia/Jakarta',
            'language' => 'en',
            'theme' => 'dark',
        ])->assertOk()->assertJsonPath('data.name', 'Updated Name');

        $this->putJson('/api/v1/user/password', [
            'current_password' => 'password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_login_is_rejected_for_invalid_credentials(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_password_reset_link_and_reset_password_work(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $token = Password::createToken($user);
        $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'reset-password123',
            'password_confirmation' => 'reset-password123',
        ])->assertOk();

        $this->assertTrue(Hash::check('reset-password123', $user->fresh()->password));
    }
}
