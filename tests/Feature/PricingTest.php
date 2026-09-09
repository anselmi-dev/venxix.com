<?php

use App\Models\User;

test('pricing page is public', function () {
    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Pricing')
            ->where('authenticated', false)
        );
});

test('pricing page marks authenticated visitors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Pricing')
            ->where('authenticated', true)
            ->where('currentFamily', 'free')
        );
});
