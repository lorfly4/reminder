<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\Reminder;
use App\Jobs\SendReminderNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6Test extends TestCase
{
    use RefreshDatabase;

    public function test_notification_preferences_can_be_read_and_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/settings/notifications')
            ->assertOk()
            ->assertJsonPath('data.in_app', true);

        $this->actingAs($user)->putJson('/api/v1/settings/notifications', [
            'email' => false,
            'web_push' => false,
            'quiet_start' => '22:00',
            'quiet_end' => '07:00',
        ])->assertOk()
            ->assertJsonPath('data.email', false)
            ->assertJsonPath('data.quiet_start', '22:00');
    }

    public function test_web_push_subscription_can_be_registered_and_removed(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v1/push-subscriptions', [
            'type' => 'web',
            'endpoint' => 'https://push.example.test/subscription',
            'p256dh' => 'p256dh-key',
            'auth' => 'auth-key',
            'device_info' => 'Test Browser',
        ])->assertCreated();

        $id = $response->json('data.id');
        $this->actingAs($user)->deleteJson("/api/v1/push-subscriptions/$id")
            ->assertOk();
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $id]);
    }

    public function test_fcm_subscription_can_be_registered(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/push-subscriptions', [
            'type' => 'fcm',
            'token' => 'test-fcm-token',
            'device_info' => 'Test Device',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'fcm');
    }

    public function test_email_delivery_uses_notification_channel_and_is_recorded(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $reminder = Reminder::factory()->create([
            'task_id' => $task->id,
            'channels' => ['email'],
            'status' => 'processing',
        ]);

        (new SendReminderNotification($reminder->id))->handle();

        Notification::assertSentTo(
            $user,
            TaskReminderNotification::class,
        );
        $this->assertDatabaseHas('notification_deliveries', [
            'reminder_id' => $reminder->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_quiet_hours_defer_delivery_and_restore_pending_status(): void
    {
        $user = User::factory()->create();
        $user->notificationSetting()->create([
            'email' => true,
            'quiet_start' => '00:00',
            'quiet_end' => '23:59',
        ]);
        $task = Task::factory()->create(['user_id' => $user->id]);
        $reminder = Reminder::factory()->create([
            'task_id' => $task->id,
            'channels' => ['email'],
            'status' => 'processing',
        ]);

        (new SendReminderNotification($reminder->id))->handle();

        $this->assertSame('pending', $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->snoozed_until);
        $this->assertDatabaseHas('notification_deliveries', [
            'reminder_id' => $reminder->id,
            'channel' => 'email',
            'status' => 'pending',
        ]);
    }

    public function test_other_user_cannot_remove_subscription(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $subscription = $owner->pushSubscriptions()->create([
            'type' => 'fcm',
            'token' => 'owned-token',
        ]);

        $this->actingAs($other)->deleteJson("/api/v1/push-subscriptions/{$subscription->id}")
            ->assertForbidden();
    }
}
