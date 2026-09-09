<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AppAlert extends Notification
{
    public function __construct(
        public string $title,
        public string $description,
        public string $icon = '🔔',
        public ?string $url = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, description: string, icon: string, url: string|null}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'url' => $this->url,
        ];
    }
}
