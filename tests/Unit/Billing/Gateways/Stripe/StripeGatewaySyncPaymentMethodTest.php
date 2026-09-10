<?php

use App\Billing\Gateways\Stripe\StripeGateway;
use App\Models\User;

test('sync payment method does nothing for a user without a stripe customer id', function () {
    $user = new User(['stripe_id' => null]);

    // Would throw/attempt a network call if the null-guard were removed,
    // since no Stripe API key is configured in this test process.
    (new StripeGateway)->syncPaymentMethod($user);

    expect($user->pm_brand)->toBeNull();
});
