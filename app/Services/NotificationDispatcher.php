<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Minishlink\WebPush\WebPush;

class NotificationDispatcher
{
    public function email(Task $task): void
    {
        $task->user->notify(new TaskReminderNotification($task, $task->reminders()->latest()->first()));
    }

    public function webPush(Task $task, string $payload): array
    {
        $privateKey = env('VAPID_PRIVATE_KEY');
        $publicKey = env('VAPID_PUBLIC_KEY');
        $subject = env('VAPID_SUBJECT', 'mailto:admin@localhost');
        if (! $privateKey || ! $publicKey) {
            throw new \RuntimeException('VAPID credentials are not configured.');
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $errors = [];
        foreach ($task->user->pushSubscriptions()->where('type', 'web')->get() as $subscription) {
            $report = $webPush->sendOneNotification([
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->p256dh,
                    'auth' => $subscription->auth,
                ],
            ], $payload);
            if (! $report->isSuccess()) {
                $statusCode = method_exists($report, 'getResponse') && $report->getResponse()
                    ? $report->getResponse()->getStatusCode()
                    : null;
                if (in_array($statusCode, [404, 410], true)) {
                    $subscription->delete();
                }
                $errors[] = $report->getReason();
            } else {
                $subscription->update(['last_used_at' => now()]);
            }
        }
        if ($errors) {
            throw new \RuntimeException(implode('; ', $errors));
        }
        return ['sent' => true];
    }

    public function fcm(Task $task, string $payload): array
    {
        $credentialsPath = env('FCM_CREDENTIALS_PATH');
        $projectId = env('FCM_PROJECT_ID');
        if (! $credentialsPath || ! $projectId || ! file_exists($credentialsPath)) {
            throw new \RuntimeException('FCM credentials are not configured.');
        }

        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            json_decode((string) file_get_contents($credentialsPath), true),
        );
        $authToken = $credentials->fetchAuthToken();
        $accessToken = $authToken['access_token'] ?? null;
        if (! $accessToken) {
            throw new \RuntimeException('FCM access token could not be obtained.');
        }

        $errors = [];
        foreach ($task->user->pushSubscriptions()->where('type', 'fcm')->get() as $subscription) {
            $response = Http::withToken($accessToken)->post(
                'https://fcm.googleapis.com/v1/projects/'.$projectId.'/messages:send',
                [
                    'message' => [
                        'token' => $subscription->token,
                        'notification' => [
                            'title' => 'Pengingat tugas',
                            'body' => 'Task '.$task->title.' membutuhkan perhatian Anda.',
                        ],
                        'data' => ['payload' => $payload],
                    ],
                ],
            );
            if ($response->successful()) {
                $subscription->update(['last_used_at' => now()]);
                continue;
            }
            $message = $response->json('error.message') ?? 'FCM delivery failed';
            if (in_array($response->status(), [404, 410], true)) {
                $subscription->delete();
            }
            $errors[] = $message;
        }
        if ($errors) {
            throw new \RuntimeException(implode('; ', $errors));
        }
        return ['sent' => true];
    }
}
