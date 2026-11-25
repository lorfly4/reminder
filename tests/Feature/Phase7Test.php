<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7Test extends TestCase
{
    use RefreshDatabase;

    public function test_settings_can_be_read_and_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.timezone', 'Asia/Jakarta');

        $this->actingAs($user)->putJson('/api/v1/settings', [
            'timezone' => 'Asia/Makassar',
            'language' => 'en',
            'theme' => 'dark',
        ])->assertOk()
            ->assertJsonPath('data.timezone', 'Asia/Makassar')
            ->assertJsonPath('data.language', 'en')
            ->assertJsonPath('data.theme', 'dark');
    }

    public function test_user_can_export_personal_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api/v1/settings/export')
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertDownload();
    }

    public function test_account_delete_requires_valid_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/v1/settings/account', [
            'password' => 'wrong-password',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->deleteJson('/api/v1/settings/account', [
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/up')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
