<?php

namespace App\Listeners;

use App\Billing\PlanAccess;
use App\Models\User;
use App\Notifications\InstallmentDueSoon;
use App\Services\PushNotificationService;
use Illuminate\Notifications\Events\NotificationSent;

class SendWebPushNotification
{
    public function __construct(
        private PushNotificationService $pushNotifications,
        private PlanAccess $planAccess,
    ) {}

    public function handle(NotificationSent $event): void
    {
        if (! $event->notifiable instanceof User) {
            return;
        }

        if (! $this->pushNotifications->isConfigured()) {
            return;
        }

        if ($event->notification instanceof InstallmentDueSoon
            && ! $this->planAccess->wantsReminders($event->notifiable)) {
            return;
        }

        if (! method_exists($event->notification, 'toArray')) {
            return;
        }

        $data = $event->notification->toArray($event->notifiable);

        if (! isset($data['title'])) {
            return;
        }

        $this->pushNotifications->sendToUser(
            $event->notifiable,
            (string) $data['title'],
            (string) ($data['description'] ?? ''),
            isset($data['url']) ? (string) $data['url'] : null,
        );
    }
}
