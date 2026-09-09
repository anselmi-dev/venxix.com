<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    public function isConfigured(): bool
    {
        return filled(config('push.vapid.public_key'))
            && filled(config('push.vapid.private_key'));
    }

    public function sendToUser(User $user, string $title, string $body, ?string $url = null): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => (string) config('push.vapid.subject'),
                'publicKey' => (string) config('push.vapid.public_key'),
                'privateKey' => (string) config('push.vapid.private_key'),
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ], JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'keys' => [
                        'p256dh' => $subscription->public_key,
                        'auth' => $subscription->auth_token,
                    ],
                    'contentEncoding' => $subscription->content_encoding,
                ]),
                $payload,
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::query()
                    ->where('endpoint', $report->getEndpoint())
                    ->delete();
            }
        }
    }
}
