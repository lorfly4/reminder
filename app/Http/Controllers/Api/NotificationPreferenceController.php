<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = $request->user()->notificationSetting()->firstOrCreate([
            'user_id' => $request->user()->id,
        ], [
            'in_app' => true,
            'email' => true,
            'web_push' => true,
            'fcm' => true,
        ]);
        return response()->json(['data' => $settings, 'meta' => null, 'message' => null]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'in_app' => ['sometimes', 'boolean'],
            'email' => ['sometimes', 'boolean'],
            'web_push' => ['sometimes', 'boolean'],
            'fcm' => ['sometimes', 'boolean'],
            'quiet_start' => ['nullable', 'date_format:H:i'],
            'quiet_end' => ['nullable', 'date_format:H:i'],
        ]);
        $settings = $request->user()->notificationSetting()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );
        return response()->json(['data' => $settings, 'meta' => null, 'message' => 'Notification preferences updated']);
    }
}
