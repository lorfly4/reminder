<?php

namespace Tests\Feature;

use App\Jobs\SendReminderNotification;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_reminder_is_claimed_and_dispatched_once(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $reminder = Reminder::factory()->create([
            'task_id' => $task->id,
            'remind_at' => now()->subMinute(),
        ]);

        $affected = Reminder::whereKey($reminder->id)->where('status', 'pending')->update(['status' => 'processing']);
        $this->assertSame(1, $affected);
        dispatch(new SendReminderNotification($reminder->id));
        Queue::assertPushed(SendReminderNotification::class, 1);
    }

    public function test_job_is_idempotent_and_delivers_once(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $reminder = Reminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'processing',
        ]);

        Cache::clear();
        (new SendReminderNotification($reminder->id))->handle();
        (new SendReminderNotification($reminder->id))->handle();

        $this->assertSame('sent', $reminder->fresh()->status);
        $this->assertDatabaseCount('notification_deliveries', 1);
        $this->assertSame(1, $reminder->fresh()->deliveries()->count());
    }

    public function test_completed_task_skips_pending_reminder(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'completed_at' => now()]);
        $reminder = Reminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'processing',
        ]);

        (new SendReminderNotification($reminder->id))->handle();
        $this->assertSame('skipped', $reminder->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_owner_can_create_toggle_snooze_and_delete_reminder(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id, 'deadline_at' => now()->addDay()]);

        $create = $this->actingAs($user)->postJson('/api/v1/reminders', [
            'task_id' => $task->id,
            'remind_at' => now()->addHour()->toIso8601String(),
            'channels' => ['in_app'],
        ]);
        $create->assertCreated();
        $reminderId = $create->json('data.id');

        $this->patchJson("/api/v1/reminders/$reminderId/toggle")->assertOk();
        $this->postJson("/api/v1/reminders/$reminderId/snooze", ['minutes' => 30])->assertOk();
        $this->deleteJson("/api/v1/reminders/$reminderId")->assertOk();
    }

    public function test_cross_user_cannot_create_reminder_for_foreign_task(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->postJson('/api/v1/reminders', [
            'task_id' => $task->id,
            'remind_at' => now()->addHour()->toIso8601String(),
            'channels' => ['in_app'],
        ])->assertUnprocessable();
    }
}
