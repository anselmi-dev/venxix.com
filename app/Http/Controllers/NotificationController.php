<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString() === 'unread' ? 'unread' : 'all';
        $user = $request->user();

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->get();

        return Inertia::render('Notifications/Index', [
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
            'totalCount' => $user->notifications()->count(),
            'groups' => $this->groupByDay($notifications),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $model = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $model->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return back();
    }

    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return list<array{label: string, items: list<array<string, mixed>>}>
     */
    private function groupByDay($notifications): array
    {
        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $weekStart = $today->copy()->startOfWeek();

        $buckets = [
            'today' => [],
            'yesterday' => [],
            'this_week' => [],
            'older' => [],
        ];

        foreach ($notifications as $notification) {
            $createdAt = Carbon::parse($notification->created_at);
            $payload = $this->present($notification);

            if ($createdAt->greaterThanOrEqualTo($today)) {
                $buckets['today'][] = $payload;
            } elseif ($createdAt->greaterThanOrEqualTo($yesterday)) {
                $buckets['yesterday'][] = $payload;
            } elseif ($createdAt->greaterThanOrEqualTo($weekStart)) {
                $buckets['this_week'][] = $payload;
            } else {
                $buckets['older'][] = $payload;
            }
        }

        return array_values(collect($buckets)
            ->filter(fn (array $items) => $items !== [])
            ->map(fn (array $items, string $key) => [
                'label' => __('ui.notifications.'.$key),
                'items' => $items,
            ])
            ->values()
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'icon' => $data['icon'] ?? '🔔',
            'title' => $data['title'] ?? __('ui.notifications.fallback_title'),
            'description' => $data['description'] ?? '',
            'url' => $data['url'] ?? null,
            'unread' => $notification->read_at === null,
            'time' => Carbon::parse($notification->created_at)->format('H:i'),
        ];
    }
}
