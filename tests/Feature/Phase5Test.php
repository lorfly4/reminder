<?php

namespace Tests\Feature;

use App\Models\RecurringRule;
use App\Models\Task;
use App\Models\User;
use App\Services\RecurrenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase5Test extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_returns_only_user_events_in_requested_range(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Calendar task',
            'start_at' => now()->addDay(),
            'deadline_at' => now()->addDay()->addHour(),
        ]);
        Task::factory()->create([
            'user_id' => $other->id,
            'title' => 'Private task',
            'start_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->getJson('/api/v1/calendar/events?start='.urlencode(now()->startOfDay()->toIso8601String()).'&end='.urlencode(now()->addWeek()->toIso8601String()))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Calendar task');
    }

    public function test_recurring_rule_generates_no_duplicate_occurrence(): void
    {
        $user = User::factory()->create();
        $template = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Daily standup',
            'start_at' => now()->startOfHour(),
            'deadline_at' => now()->startOfHour()->addHour(),
        ]);
        $rule = RecurringRule::create([
            'task_id' => $template->id,
            'frequency' => 'daily',
            'interval' => 1,
            'next_run_at' => now()->subMinute(),
            'is_enabled' => true,
        ]);

        $service = app(RecurrenceService::class);
        $this->assertSame(1, $service->generateDue());
        $this->assertSame(0, $service->generateDue());
        $this->assertSame(1, Task::where('recurring_template_id', $template->id)->count());
    }

    public function test_recurring_endpoint_updates_rule(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->putJson("/api/v1/tasks/{$task->id}/recurring", [
            'frequency' => 'monthly',
            'interval' => 1,
            'is_enabled' => true,
        ])->assertOk()->assertJsonPath('data.frequency', 'monthly');
    }

    public function test_analytics_overview_uses_database_counts(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'completed_at' => now()]);
        Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);

        $this->actingAs($user)->getJson('/api/v1/analytics/overview')
            ->assertOk()
            ->assertJsonPath('data.completed', 1)
            ->assertJsonPath('data.pending', 1)
            ->assertJsonPath('data.completion_rate', 50);
    }
}
