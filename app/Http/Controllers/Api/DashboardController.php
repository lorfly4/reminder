<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $base = $request->user()->tasks()->whereNull('archived_at');
        $now = now();
        $data = [
            'total' => (clone $base)->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'pending' => (clone $base)->where('status', '!=', 'completed')->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'today_tasks' => (clone $base)->where(function ($q) use ($now) {
                $q->whereDate('start_at', $now->toDateString())->orWhereDate('deadline_at', $now->toDateString());
            })->where('status', '!=', 'completed')->orderBy('deadline_at')->limit(10)->get(),
            'recent_activity' => $request->user()->activityLogs()->latest()->limit(10)->get(),
        ];
        return response()->json(['data' => $data, 'meta' => null, 'message' => null]);
    }
}
