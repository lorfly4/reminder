<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\NotificationController;
use App\Models\DatabaseNotification;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\RecurringTaskController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\SettingsController;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn (): JsonResponse => response()->json([
        'data' => [
            'status' => 'ok',
            'service' => 'remindly-api',
            'timestamp' => now()->toIso8601String(),
        ],
        'meta' => null,
        'message' => 'Service is healthy',
    ]));

    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);
        Route::put('/user/password', [AuthController::class, 'updatePassword']);

        Route::get('/dashboard', DashboardController::class);
        Route::apiResource('tasks', TaskController::class);
        Route::patch('/tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::patch('/tasks/{task}/reopen', [TaskController::class, 'reopen']);
        Route::patch('/tasks/{task}/archive', [TaskController::class, 'archive']);
        Route::patch('/tasks/{task}/unarchive', [TaskController::class, 'unarchive']);
        Route::patch('/tasks/{task}/reschedule', [TaskController::class, 'reschedule']);
        Route::apiResource('categories', CategoryController::class)->except(['show']);
        Route::get('/activity-logs', ActivityLogController::class);
        Route::get('/reminders', [ReminderController::class, 'index']);
        Route::post('/reminders', [ReminderController::class, 'store']);
        Route::put('/reminders/{reminder}', [ReminderController::class, 'update']);
        Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy']);
        Route::patch('/reminders/{reminder}/toggle', [ReminderController::class, 'toggle']);
        Route::post('/reminders/{reminder}/snooze', [ReminderController::class, 'snooze']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
        Route::get('/calendar/events', CalendarController::class);
        Route::put('/tasks/{task}/recurring', [RecurringTaskController::class, 'update']);
        Route::delete('/tasks/{task}/recurring', [RecurringTaskController::class, 'destroy']);
        Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
        Route::get('/analytics/productivity', [AnalyticsController::class, 'productivity']);
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
        Route::delete('/push-subscriptions/{pushSubscription}', [PushSubscriptionController::class, 'destroy']);
        Route::get('/settings/notifications', [NotificationPreferenceController::class, 'show']);
        Route::put('/settings/notifications', [NotificationPreferenceController::class, 'update']);
        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);
        Route::get('/settings/export', [SettingsController::class, 'export']);
        Route::delete('/settings/account', [SettingsController::class, 'destroy']);
    });
});
