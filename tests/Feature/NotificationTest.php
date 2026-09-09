<?php

use App\Models\User;
use App\Notifications\AppAlert;

test('guests are redirected to login from notifications', function () {
    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));
});

test('notifications page lists the users alerts grouped by day', function () {
    $user = User::factory()->create();
    $user->notify(new AppAlert(
        title: 'Vence en 2 días',
        description: 'Fotostanisic — cuota 5/10',
        icon: '📅',
        url: '/panel',
    ));

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->where('unreadCount', 1)
            ->where('totalCount', 1)
            ->has('groups', 1)
            ->where('groups.0.items.0.title', 'Vence en 2 días')
        );
});

test('user can mark a notification as read', function () {
    $user = User::factory()->create();
    $user->notify(new AppAlert('Hola', 'Bienvenido'));
    $notification = $user->notifications()->first();

    $this->actingAs($user)
        ->patch(route('notifications.read', $notification))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('user cannot mark another users notification as read', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $owner->notify(new AppAlert('Privada', 'No es tuya'));
    $notification = $owner->notifications()->first();

    $this->actingAs($other)
        ->patch(route('notifications.read', $notification))
        ->assertNotFound();
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();
    $user->notify(new AppAlert('Uno', 'Primera'));
    $user->notify(new AppAlert('Dos', 'Segunda'));

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('unread filter hides read notifications', function () {
    $user = User::factory()->create();
    $user->notify(new AppAlert('Leída', 'Ya vista'));
    $user->notifications()->latest()->first()->markAsRead();
    $user->notify(new AppAlert('Nueva', 'Sin leer'));

    $this->actingAs($user)
        ->get(route('notifications.index', ['filter' => 'unread']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filter', 'unread')
            ->where('unreadCount', 1)
            ->has('groups.0.items', 1)
            ->where('groups.0.items.0.title', 'Nueva')
        );
});
