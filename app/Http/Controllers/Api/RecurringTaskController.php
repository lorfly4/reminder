<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringTaskController extends Controller
{
    public function update(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'frequency' => ['required', 'in:daily,weekly,monthly,yearly,custom'],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'by_weekday' => ['nullable', 'array'],
            'until_at' => ['nullable', 'date'],
            'count' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);
        $rule = $task->recurringRule()->updateOrCreate(
            ['task_id' => $task->id],
            [
                ...$validated,
                'next_run_at' => $task->deadline_at ?? $task->start_at ?? now()->addDay(),
            ],
        );
        return response()->json(['data' => $rule, 'meta' => null, 'message' => 'Recurring rule updated']);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);
        $task->recurringRule()->delete();
        return response()->json(['data' => null, 'meta' => null, 'message' => 'Recurring rule deleted']);
    }
}
