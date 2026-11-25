<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Models\DatabaseNotification;
use App\Models\Reminder;
use App\Notifications\TaskReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Services\NotificationDispatcher;
use App\Services\NotificationPreferenceService;

class SendReminderNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public int $reminderId)
    {
    }

    public function handle(): void
    {
        $lock = Cache::lock('reminder-'.$this->reminderId, 540);
        if (! $lock->get()) {
            return;
        }

        try {
            $reminder = Reminder::with('task.user')->find($this->reminderId);
            if (! $reminder || $reminder->status !== 'processing') {
                return;
            }

            $task = $reminder->task;
            if (! $task || $task->trashed() || $task->status === 'completed' || $task->archived_at) {
                $reminder->update(['status' => 'skipped', 'last_error' => null]);
                return;
            }

            foreach ($reminder->channels as $channel) {
                $delivery = NotificationDelivery::firstOrCreate(
                    ['reminder_id' => $reminder->id, 'channel' => $channel],
                    ['status' => 'pending', 'attempts' => 0],
                );
                if ($delivery->status === 'sent') {
                    continue;
                }

                try {
                    $preferences = app(NotificationPreferenceService::class);
                    if (! $preferences->channelEnabled($task->user, $channel)) {
                        $delivery->update(['status' => 'failed', 'error' => 'Channel disabled by user preference']);
                        continue;
                    }
                    if ($channel !== 'in_app' && $preferences->quietHours($task->user)) {
                        $delivery->update(['status' => 'pending', 'error' => 'Deferred by quiet hours']);
                        continue;
                    }
                    $delivery->increment('attempts');
                    if ($channel === 'in_app') {
                        DatabaseNotification::firstOrCreate(
                            ['dedupe_key' => $reminder->dedupe_key.'-in-app'],
                            [
                                'user_id' => $task->user_id,
                                'task_id' => $task->id,
                                'type' => 'task_reminder',
                                'title' => 'Pengingat tugas',
                                'message' => 'Task "'.$task->title.'" membutuhkan perhatian Anda.',
                            ],
                        );
                    } elseif ($channel === 'email') {
                        app(NotificationDispatcher::class)->email($task);
                    } elseif ($channel === 'web_push') {
                        app(NotificationDispatcher::class)->webPush($task, json_encode([
                            'title' => 'Pengingat tugas',
                            'body' => 'Task '.$task->title.' membutuhkan perhatian Anda.',
                            'url' => url('/'),
                        ], JSON_UNESCAPED_SLASHES));
                    } elseif ($channel === 'fcm') {
                        app(NotificationDispatcher::class)->fcm($task, json_encode([
                            'title' => 'Pengingat tugas',
                            'body' => 'Task '.$task->title.' membutuhkan perhatian Anda.',
                        ], JSON_UNESCAPED_SLASHES));
                    }
                    $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                } catch (\Throwable $exception) {
                    $delivery->update([
                        'status' => 'failed',
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $deliveries = $reminder->deliveries()->get();
            $hasFailure = $deliveries->contains(fn ($delivery) => $delivery->status === 'failed');
            $hasPending = $deliveries->contains(fn ($delivery) => $delivery->status === 'pending');
            if ($hasPending) {
                $quietEnd = app(NotificationPreferenceService::class)->quietEnd($task->user);
                $reminder->update([
                    'status' => 'pending',
                    'snoozed_until' => $quietEnd,
                    'last_error' => 'Deferred by quiet hours',
                ]);
                return;
            }
            $reminder->update([
                'status' => $hasFailure ? 'failed' : 'sent',
                'sent_at' => $hasFailure ? null : now(),
                'attempts' => $reminder->attempts + 1,
                'last_error' => $hasFailure ? $deliveries->where('status', 'failed')->pluck('error')->filter()->join('; ') : null,
            ]);
        } finally {
            $lock->release();
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Reminder::whereKey($this->reminderId)->update([
            'status' => 'failed',
            'last_error' => $exception?->getMessage(),
        ]);
    }
}
