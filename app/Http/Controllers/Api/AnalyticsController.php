<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $start = $request->date('start') ?? now()->startOfMonth();
        $end = $request->date('end') ?? now()->endOfMonth();
        $tasks = $request->user()->tasks()->whereBetween('created_at', [$start, $end]);

        $data = [
            'completed' => (clone $tasks)->where('status', 'completed')->count(),
            'pending' => (clone $tasks)->where('status', '!=', 'completed')->count(),
            'overdue' => (clone $tasks)->overdue()->count(),
            'completion_rate' => (clone $tasks)->count()
                ? round((clone $tasks)->where('status', 'completed')->count() / (clone $tasks)->count() * 100, 2)
                : 0,
        ];
        return response()->json(['data' => $data, 'meta' => null, 'message' => null]);
    }

    public function productivity(Request $request): JsonResponse
    {
        $days = max(1, min(365, $request->integer('days', 30)));
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = $request->user()->tasks()
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as completed')
            ->where('status', 'completed')
            ->where('completed_at', '>=', $start)
            ->groupByRaw('DATE(completed_at)')
            ->orderByRaw('DATE(completed_at)')
            ->get();

        return response()->json([
            'data' => [
                'labels' => $rows->pluck('date')->values(),
                'completed' => $rows->pluck('completed')->values(),
            ],
            'meta' => null,
            'message' => null,
        ]);
    }
}
