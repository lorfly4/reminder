<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_owned_tasks_and_categories(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Kerja', 'color' => '#6366F1']);

        $this->actingAs($user)->postJson('/api/v1/tasks', [
            'title' => 'Submit proposal',
            'category_id' => $category->id,
            'deadline_at' => now()->addDay()->toIso8601String(),
            'priority' => 'high',
        ])->assertCreated()->assertJsonPath('data.title', 'Submit proposal');

        $task = Task::firstOrFail();
        $this->actingAs($user)->patchJson("/api/v1/tasks/{$task->id}/complete")
            ->assertOk()->assertJsonPath('data.status', 'completed');
        $this->actingAs($user)->patchJson("/api/v1/tasks/{$task->id}/archive")
            ->assertOk();
        $this->actingAs($user)->patchJson("/api/v1/tasks/{$task->id}/unarchive")
            ->assertOk();
    }

    public function test_cross_user_access_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->getJson("/api/v1/tasks/{$task->id}")
            ->assertForbidden();
        $this->actingAs($other)->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Hacked'])
            ->assertForbidden();
        $this->actingAs($other)->deleteJson("/api/v1/tasks/{$task->id}")
            ->assertForbidden();
    }

    public function test_task_validation_rejects_deadline_before_start(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/tasks', [
            'title' => 'Invalid task',
            'start_at' => now()->addDay()->toIso8601String(),
            'deadline_at' => now()->toIso8601String(),
            'priority' => 'medium',
        ])->assertUnprocessable();
    }

    public function test_category_delete_keeps_tasks_and_dashboard_counts(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Other', 'color' => '#64748B']);
        $task = Task::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        $this->actingAs($user)->deleteJson("/api/v1/categories/{$category->id}")
            ->assertOk();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'category_id' => null]);

        $this->actingAs($user)->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.pending', 1);
    }
}
