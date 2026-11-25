<?php

namespace App\Services;

use App\Models\RecurringRule;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecurrenceService
{
    public function generateDue(): int
    {
        $generated = 0;
        RecurringRule::where('is_enabled', true)
            ->where('next_run_at', '<=', now())
            ->with('task')
            ->chunkById(100, function ($rules) use (&$generated): void {
                foreach ($rules as $rule) {
                    if (! $rule->task || ($rule->until_at && $rule->next_run_at->gt($rule->until_at))) {
                        continue;
                    }

                    $occurrence = $rule->next_run_at;
                    DB::transaction(function () use ($rule, $occurrence, &$generated): void {
                        $template = $rule->task;
                        $exists = Task::where('recurring_template_id', $template->id)
                            ->where('occurrence_at', $occurrence)
                            ->exists();
                        if ($exists) {
                            $rule->update([
                                'next_run_at' => $this->nextRun($rule, $occurrence),
                                'last_generated_at' => $occurrence,
                            ]);
                            return;
                        }

                        Task::create([
                            'user_id' => $template->user_id,
                            'category_id' => $template->category_id,
                            'recurring_template_id' => $template->id,
                            'title' => $template->title,
                            'description' => $template->description,
                            'start_at' => $occurrence,
                            'deadline_at' => $template->deadline_at
                                ? $occurrence->copy()->addSeconds($template->start_at?->diffInSeconds($template->deadline_at) ?? 0)
                                : null,
                            'occurrence_at' => $occurrence,
                            'priority' => $template->priority,
                            'status' => 'todo',
                        ]);
                        $rule->update([
                            'next_run_at' => $this->nextRun($rule, $occurrence),
                            'last_generated_at' => $occurrence,
                        ]);
                        $generated++;
                    });
                }
            });
        return $generated;
    }

    public function nextRun(RecurringRule $rule, Carbon $from): Carbon
    {
        $interval = max(1, $rule->interval);
        return match ($rule->frequency) {
            'daily' => $from->copy()->addDays($interval),
            'weekly' => $from->copy()->addWeeks($interval),
            'monthly' => $this->addMonthsSafely($from, $interval),
            'yearly' => $this->addMonthsSafely($from, 12 * $interval),
            default => $from->copy()->addDays($interval),
        };
    }

    private function addMonthsSafely(Carbon $date, int $months): Carbon
    {
        $day = $date->day;
        $result = $date->copy()->addMonthsNoOverflow($months);
        return $result->day(min($day, $result->daysInMonth));
    }
}
