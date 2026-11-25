<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:web,fcm'],
            'endpoint' => ['required_if:type,web', 'nullable', 'url', 'max:4096'],
            'token' => ['required_if:type,fcm', 'nullable', 'string', 'max:4096'],
            'p256dh' => ['required_if:type,web', 'nullable', 'string'],
            'auth' => ['required_if:type,web', 'nullable', 'string'],
            'device_info' => ['nullable', 'string', 'max:255'],
        ]);

        $attributes = [
            'type' => $validated['type'],
            'device_info' => $validated['device_info'] ?? null,
            'last_used_at' => now(),
        ];
        if ($validated['type'] === 'web') {
            $attributes += [
                'endpoint' => $validated['endpoint'],
                'endpoint_hash' => hash('sha256', $validated['endpoint']),
                'p256dh' => $validated['p256dh'],
                'auth' => $validated['auth'],
            ];
        } else {
            $attributes += ['token' => $validated['token']];
        }

        $subscription = $request->user()->pushSubscriptions()->updateOrCreate(
            $validated['type'] === 'web'
                ? ['type' => 'web', 'endpoint_hash' => $attributes['endpoint_hash']]
                : ['type' => 'fcm', 'token' => $attributes['token']],
            $attributes,
        );

        return response()->json(['data' => $subscription, 'meta' => null, 'message' => 'Push subscription registered'], 201);
    }

    public function destroy(Request $request, PushSubscription $pushSubscription): JsonResponse
    {
        abort_unless($pushSubscription->user_id === $request->user()->id, 403);
        $pushSubscription->delete();
        return response()->json(['data' => null, 'meta' => null, 'message' => 'Push subscription removed']);
    }
}
