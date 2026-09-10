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
