<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'availableLocales' => config('app.supported_locales'),
            'translations' => trans('ui'),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'onboarding_completed_at' => $request->user()->onboarding_completed_at,
                ] : null,
            ],
            'notifications' => $request->user() ? [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'latest' => $request->user()->notifications()->latest()->limit(5)->get()->map(fn (DatabaseNotification $notification) => [
                    'id' => $notification->id,
                    'icon' => $notification->data['icon'] ?? '🔔',
                    'title' => $notification->data['title'] ?? __('ui.notifications.fallback_title'),
                    'description' => $notification->data['description'] ?? '',
                    'url' => $notification->data['url'] ?? null,
                    'unread' => $notification->read_at === null,
                    'time' => Carbon::parse($notification->created_at)->diffForHumans(),
                ])->values()->all(),
            ] : [
                'unread_count' => 0,
                'latest' => [],
            ],
            'realtime' => [
                'enabled' => config('broadcasting.default') === 'reverb'
                    && filled(config('broadcasting.connections.reverb.key')),
                'reverb' => [
                    'key' => config('broadcasting.connections.reverb.key'),
                    'host' => config('broadcasting.connections.reverb.options.host'),
                    'port' => (int) config('broadcasting.connections.reverb.options.port', 443),
                    'scheme' => config('broadcasting.connections.reverb.options.scheme', 'https'),
                ],
            ],
            'push' => [
                'enabled' => filled(config('push.vapid.public_key')),
                'public_key' => config('push.vapid.public_key'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
