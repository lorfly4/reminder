<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\ActivityLog;
use App\Jobs\SendReminderNotification;
use App\Services\RecurrenceService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    $reminders = Reminder::query()->due()->with('task')->limit(200)->get();

    foreach ($reminders as $reminder) {
        $task = $reminder->task;
        if (! $task || $task->trashed() || $task->archived_at || $task->status === 'completed') {
            $reminder->update(['status' => $task ? 'cancelled' : 'skipped']);
            continue;
        }

        $affected = Reminder::whereKey($reminder->id)
            ->where('status', 'pending')
            ->update(['status' => 'processing']);

        if ($affected === 1) {
            dispatch(new SendReminderNotification($reminder->id));
        }
    }
})->everyMinute()->name('remindly-scheduler')->withoutOverlapping()->onOneServer();

Schedule::call(function (): void {
    Reminder::where('status', 'processing')
        ->where('updated_at', '<=', now()->subMinutes(10))
        ->update(['status' => 'pending', 'last_error' => 'Processing timeout recovered']);
})->everyFiveMinutes()->name('remindly-recovery')->withoutOverlapping()->onOneServer();

Schedule::call(function (): void {
    foreach (Task::query()->overdue()->with('user')->limit(200)->get() as $task) {
        $dedupeKey = 'overdue-'.$task->id.'-'.now()->toDateString();
        $task->user->databaseNotifications()->firstOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
                'type' => 'overdue',
                'title' => 'Task terlambat',
                'message' => 'Task "'.$task->title.'" melewati deadline.',
                'task_id' => $task->id,
            ],
        );
    }
})->hourly()->name('remindly-overdue')->withoutOverlapping()->onOneServer();

Schedule::call(function (RecurrenceService $service): void {
    $service->generateDue();
})->hourly()->name('remindly-recurring')->withoutOverlapping()->onOneServer();
