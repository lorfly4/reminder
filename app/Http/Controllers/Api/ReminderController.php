<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ReminderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reminders = Reminder::whereHas('task', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('task')
            ->latest()
            ->paginate($request->integer('per_page', 20));
        return response()->json(['data' => $reminders->items(), 'meta' => ['current_page' => $reminders->currentPage(), 'last_page' => $reminders->lastPage(), 'per_page' => $reminders->perPage(), 'total' => $reminders->total()], 'message' => null]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'integer', Rule::exists('tasks', 'id')->where(fn ($q) => $q->where('user_id', $request->user()->id)->whereNull('deleted_at'))],
            'remind_at' => ['required', 'date', 'after:now'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['in:in_app,email,web_push,fcm'],
        ]);
        $reminder = Reminder::create([
            ...$validated,
            'status' => 'pending',
            'is_enabled' => true,
            'dedupe_key' => Str::uuid()->toString(),
        ]);
        return response()->json(['data' => $reminder->load('task'), 'meta' => null, 'message' => 'Reminder created'], 201);
    }

    public function update(Request $request, Reminder $reminder): JsonResponse
    {
        abort_unless($reminder->task->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'remind_at' => ['sometimes', 'date', 'after:now'],
            'channels' => ['sometimes', 'array', 'min:1'],
            'channels.*' => ['in:in_app,email,web_push,fcm'],
        ]);
        $reminder->update($validated);
        return response()->json(['data' => $reminder->fresh('task'), 'meta' => null, 'message' => 'Reminder updated']);
    }

    public function destroy(Request $request, Reminder $reminder): JsonResponse
    {
        abort_unless($reminder->task->user_id === $request->user()->id, 403);
        $reminder->update(['status' => 'cancelled', 'is_enabled' => false]);
        return response()->json(['data' => null, 'meta' => null, 'message' => 'Reminder cancelled']);
    }

    public function toggle(Request $request, Reminder $reminder): JsonResponse
    {
        abort_unless($reminder->task->user_id === $request->user()->id, 403);
        $reminder->update(['is_enabled' => ! $reminder->is_enabled]);
        return response()->json(['data' => $reminder->fresh(), 'meta' => null, 'message' => 'Reminder toggled']);
    }

    public function snooze(Request $request, Reminder $reminder): JsonResponse
    {
        abort_unless($reminder->task->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:10080'],
        ]);
        abort_unless($reminder->status === 'pending' || $reminder->status === 'failed', 422, 'Reminder tidak dapat dijadwalkan ulang.');
        $reminder->update([
            'status' => 'pending',
            'is_enabled' => true,
            'snoozed_until' => now()->addMinutes($validated['minutes']),
        ]);
        return response()->json(['data' => $reminder->fresh(), 'meta' => null, 'message' => 'Reminder snoozed']);
    }
}
