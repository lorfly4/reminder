<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $tasks = $request->user()->tasks()
            ->with('category')
            ->where(function ($query) use ($validated): void {
                $query->whereBetween('start_at', [$validated['start'], $validated['end']])
                    ->orWhereBetween('deadline_at', [$validated['start'], $validated['end']]);
            })
            ->get();

        $events = $tasks->map(fn ($task) => [
            'id' => (string) $task->id,
            'title' => $task->title,
            'start' => $task->start_at?->toIso8601String(),
            'end' => $task->deadline_at?->toIso8601String(),
            'allDay' => ! $task->start_at,
            'backgroundColor' => match ($task->priority) {
                'urgent' => '#EF4444',
                'high' => '#F97316',
                'medium' => '#6366F1',
                default => '#10B981',
            },
            'borderColor' => $task->category?->color ?? '#94A3B8',
        ]);
        return response()->json(['data' => $events, 'meta' => null, 'message' => null]);
    }
}
