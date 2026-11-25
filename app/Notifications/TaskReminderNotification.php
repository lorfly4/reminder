<?php

namespace App\Notifications;

use App\Models\Reminder;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task, public Reminder $reminder)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengingat tugas: '.$this->task->title)
            ->line('Task "'.$this->task->title.'" membutuhkan perhatian Anda.')
            ->action('Lihat task', url('/'))
            ->line('Dikirim oleh Remindly.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_reminder',
            'task_id' => $this->task->id,
            'reminder_id' => $this->reminder->id,
            'title' => 'Pengingat tugas',
            'message' => 'Task "'.$this->task->title.'" jatuh tempo.',
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'task_reminder';
    }
}
