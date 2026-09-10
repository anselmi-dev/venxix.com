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

test('pricing page lists dLocal Go alongside the other gateways', function () {
    // Explicitly configure all three so this doesn't depend on whichever
    // gateway credentials happen to be set (or not) in the local .env.
    config([
        'services.stripe.secret' => 'sk_test_fake',
        'services.mercadopago.access_token' => 'TEST-fake',
        'services.dlocalgo.api_key' => 'fake-key',
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Pricing')
            ->has('paymentGateways', 3)
            ->where('paymentGateways.0.slug', 'stripe')
            ->where('paymentGateways.1.slug', 'mercadopago')
            ->where('paymentGateways.2.slug', 'dlocalgo')
        );
});
