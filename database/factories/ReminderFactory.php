<?php

namespace Database\Factories;

use App\Models\Reminder;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'remind_at' => now()->subMinute(),
            'channels' => ['in_app'],
            'status' => 'pending',
            'is_enabled' => true,
            'dedupe_key' => Str::uuid()->toString(),
        ];
    }
}
