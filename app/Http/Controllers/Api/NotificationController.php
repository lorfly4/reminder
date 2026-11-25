<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DatabaseNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->databaseNotifications()->latest()->paginate($request->integer('per_page', 20));
        return response()->json([
            'data' => $notifications->items(),
            'meta' => ['current_page' => $notifications->currentPage(), 'last_page' => $notifications->lastPage(), 'per_page' => $notifications->perPage(), 'total' => $notifications->total()],
            'message' => null,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => ['unread' => $request->user()->databaseNotifications()->whereNull('read_at')->count()], 'meta' => null, 'message' => null]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);
        return response()->json(['data' => $notification->fresh(), 'meta' => null, 'message' => 'Notification marked read']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->databaseNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['data' => null, 'meta' => null, 'message' => 'All notifications marked read']);
    }

    public function destroy(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->delete();
        return response()->json(['data' => null, 'meta' => null, 'message' => 'Notification deleted']);
    }
}
