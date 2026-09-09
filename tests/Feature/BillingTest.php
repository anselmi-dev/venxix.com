<?php

use App\Models\User;

test('guests are redirected to login from billing', function () {
    $this->get(route('billing'))
        ->assertRedirect(route('login'));
});

test('billing shows the free plan without invoices', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('billing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Billing/Index')
            ->where('plan.name', 'Gratis')
            ->where('plan.is_free', true)
            ->where('paymentMethod', null)
            ->where('checkoutSuccess', false)
            ->has('invoices', 0)
        );
});

test('billing lists paid invoices for the user', function () {
    $user = User::factory()->create();
    $user->invoices()->create([
        'provider' => 'stripe',
        'provider_invoice_id' => 'in_local_1',
        'description' => 'Plan Pro — mensual',
        'amount' => '199.00',
        'currency' => 'UYU',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('billing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('invoices', 1)
            ->where('invoices.0.description', 'Plan Pro — mensual')
            ->where('stats.payment_count', 1)
        );
});

test('billing marks a successful checkout query', function () {
    $user = User::factory()->pro()->create();

    $this->actingAs($user)
        ->get(route('billing', ['checkout' => 'success']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('checkoutSuccess', true)
            ->where('plan.is_free', false)
        );
});
