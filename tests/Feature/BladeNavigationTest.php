<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BladeNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke Remindly');
    }

    public function test_user_can_login_and_visit_all_blade_pages(): void
    {
        $user = User::factory()->create();

        $this->get('/tasks')->assertRedirect('/login');

        $this->actingAs($user)
            ->get('/')->assertOk()->assertSee('Halo');

        $this->actingAs($user)
            ->get('/tasks')->assertOk()->assertSee('Daftar Tugas');

        $this->actingAs($user)
            ->get('/calendar')->assertOk()->assertSee('Kalender');

        $this->actingAs($user)
            ->get('/reminders')->assertOk()->assertSee('Pengingat');

        $this->actingAs($user)
            ->get('/settings')->assertOk()->assertSee('Pengaturan');
    }

    public function test_web_login_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_create_task_through_blade_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tasks', [
            'title' => 'Task Blade',
            'priority' => 'high',
        ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Task Blade',
            'priority' => 'high',
        ]);
    }

    public function test_pwa_assets_are_public(): void
    {
        $this->get('/manifest.webmanifest')->assertOk();
        $this->get('/sw.js')->assertOk();
        $this->get('/icons/icon-192.png')->assertOk();
        $this->get('/icons/icon-512.png')->assertOk();
    }
}
