<?php

namespace App\Services;

use App\Models\User;

class NotificationPreferenceService
{
    public function channelEnabled(User $user, string $channel): bool
    {
        $settings = $user->notificationSetting()->first();
        if (! $settings) {
            return true;
        }
        return match ($channel) {
            'in_app' => $settings->in_app,
            'email' => $settings->email,
            'web_push' => $settings->web_push,
            'fcm' => $settings->fcm,
            default => false,
        };
    }

    public function quietHours(User $user): bool
    {
        $settings = $user->notificationSetting()->first();
        if (! $settings || ! $settings->quiet_start || ! $settings->quiet_end) {
            return false;
        }

        $now = now($user->timezone)->format('H:i');
        $start = substr((string) $settings->quiet_start, 0, 5);
        $end = substr((string) $settings->quiet_end, 0, 5);
        return $start <= $end
            ? $now >= $start && $now <= $end
            : $now >= $start || $now <= $end;
    }

    public function quietEnd(User $user): ?\Illuminate\Support\Carbon
    {
        $settings = $user->notificationSetting()->first();
        if (! $settings?->quiet_end) {
            return null;
        }
        $timezone = $user->timezone ?: 'Asia/Jakarta';
        $end = now($timezone)->setTimeFromTimeString(substr((string) $settings->quiet_end, 0, 5));
        if ($end->lte(now($timezone))) {
            $end->addDay();
        }
        return $end;
    }
}
