<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $notificationSetting = $user->notificationSetting()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'in_app' => true,
            'email' => true,
            'web_push' => true,
            'fcm' => true,
        ]);

        return response()->json([
            'data' => [
                'timezone' => $user->timezone,
                'language' => $user->language,
                'theme' => $user->theme,
                'notifications' => $notificationSetting,
            ],
            'meta' => null,
            'message' => null,
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());
        return $this->show($request);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user()->load([
            'categories',
            'tasks.category',
            'tasks.reminders.deliveries',
            'tasks.recurringRule',
            'activityLogs',
            'databaseNotifications',
            'pushSubscriptions',
            'notificationSetting',
        ]);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'profile' => $user->only(['id', 'name', 'email', 'timezone', 'language', 'theme', 'created_at']),
            'categories' => $user->categories,
            'tasks' => $user->tasks,
            'activity_logs' => $user->activityLogs,
            'notifications' => $user->databaseNotifications,
            'push_subscriptions' => $user->pushSubscriptions,
            'notification_settings' => $user->notificationSetting,
        ];

        $filename = 'remindly-export-'.$user->id.'-'.now()->format('YmdHis').'.json';
        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        $user->delete();

        return response()->json([
            'data' => null,
            'meta' => null,
            'message' => 'Account deleted',
        ]);
    }
}
