<?php

use App\Models\User;

test('authenticated users can store a push subscription', function () {
    config([
        'push.vapid.public_key' => 'test-public-key',
        'push.vapid.private_key' => 'test-private-key',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('push.store'), [
            'endpoint' => 'https://push.example.test/subscription-1',
            'keys' => [
                'p256dh' => 'public-key-data',
                'auth' => 'auth-token-data',
            ],
            'content_encoding' => 'aesgcm',
        ])
        ->assertOk()
        ->assertJson(['status' => 'subscribed']);

    expect($user->pushSubscriptions()->count())->toBe(1);
});

test('push subscriptions are disabled without vapid keys', function () {
    config([
        'push.vapid.public_key' => null,
        'push.vapid.private_key' => null,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('push.store'), [
            'endpoint' => 'https://push.example.test/subscription-1',
            'keys' => [
                'p256dh' => 'public-key-data',
                'auth' => 'auth-token-data',
            ],
        ])
        ->assertStatus(503);
});

test('users can remove their push subscription', function () {
    config([
        'push.vapid.public_key' => 'test-public-key',
        'push.vapid.private_key' => 'test-private-key',
    ]);

    $user = User::factory()->create();
    $subscription = $user->pushSubscriptions()->create([
        'endpoint' => 'https://push.example.test/subscription-1',
        'public_key' => 'public-key-data',
        'auth_token' => 'auth-token-data',
        'content_encoding' => 'aesgcm',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('push.destroy'), [
            'endpoint' => $subscription->endpoint,
        ])
        ->assertNoContent();

    expect($user->pushSubscriptions()->count())->toBe(0);
});
