<?php

use App\Models\User;

test('incomplete onboarding redirects to welcome', function () {
    $user = User::factory()->onboardingIncomplete()->create();

    $this->actingAs($user)
        ->get(route('panel'))
        ->assertRedirect(route('onboarding.welcome'));
});

test('onboarding skip marks user as completed', function () {
    $user = User::factory()->onboardingIncomplete()->create();

    $this->actingAs($user)
        ->post(route('onboarding.skip'))
        ->assertRedirect(route('panel'));

    expect($user->fresh()->onboarding_completed_at)->not->toBeNull();
});

test('onboarding currency step completes onboarding', function () {
    $user = User::factory()->onboardingIncomplete()->create();

    $this->actingAs($user)
        ->post(route('onboarding.currency.store'), [
            'preferred_currency' => 'USD',
            'show_both_currencies' => true,
        ])
        ->assertRedirect(route('panel'));

    $user->refresh();

    expect($user->onboarding_completed_at)->not->toBeNull();
    expect($user->preferred_currency->value)->toBe('USD');
});

test('completed onboarding cannot revisit wizard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding.welcome'))
        ->assertRedirect(route('panel'));
});
