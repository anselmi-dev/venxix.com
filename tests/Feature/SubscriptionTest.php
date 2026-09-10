<?php

use App\Billing\ActivatePaidPlan;
use App\Billing\Gateways\Stripe\ProcessStripeWebhook;
use App\Billing\PaymentGatewayManager;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\User;
use Revoltify\Subscriptionify\Models\Plan;

test('guests are redirected to login from checkout', function () {
    $this->post(route('checkout.store', ['plan' => 'pro']), [
        'interval' => 'monthly',
    ])->assertRedirect(route('login'));
});

test('checkout rejects an invalid billing interval', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('checkout.store', ['plan' => 'pro']), [
            'interval' => 'weekly',
            'gateway' => 'stripe',
        ])
        ->assertSessionHasErrors('interval');
});

test('fake checkout activates the pro plan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('checkout.store', ['plan' => 'pro']), [
            'interval' => 'monthly',
            'gateway' => 'stripe',
        ])
        ->assertRedirect(route('billing', ['checkout' => 'success']));

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->getPlan()->getSlug())->toBe('pro');
    expect(AuditLog::where('user_id', $user->id)->where('action', 'plan_changed')->exists())->toBeTrue();
});

test('checkout sends Inertia XHR requests to the gateway via a full page visit', function () {
    // Regression test: a plain redirect to an external checkout URL (Stripe,
    // dLocal Go, Mercado Pago) gets followed as another XHR request by
    // Inertia's <Form>, which the browser then blocks on CORS. Inertia
    // needs a 409 + X-Inertia-Location instead, so it does a real
    // window.location visit.
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('checkout.store', ['plan' => 'pro']), [
            'interval' => 'monthly',
            'gateway' => 'stripe',
        ]);

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))->toBe(route('billing', ['checkout' => 'success']));
});

test('switching to the free plan from checkout updates the subscription', function () {
    $user = User::factory()->pro()->create();

    $this->actingAs($user)
        ->post(route('checkout.store', ['plan' => 'free']), [
            'interval' => 'monthly',
            'gateway' => 'stripe',
        ])
        ->assertRedirect(route('billing'));

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->getPlan()->getSlug())->toBe('free');
});

test('cancelling a paid plan starts the grace period', function () {
    $user = User::factory()->pro()->create();

    $this->actingAs($user)
        ->post(route('billing.cancel'))
        ->assertRedirect();

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->onGracePeriod())->toBeTrue();
    expect(AuditLog::where('user_id', $user->id)->where('action', 'plan_cancelled')->exists())->toBeTrue();
});

test('a cancelled paid plan can be resumed during the grace period', function () {
    $user = User::factory()->pro()->create();

    $this->actingAs($user)->post(route('billing.cancel'));

    $this->actingAs($user)
        ->post(route('billing.resume'))
        ->assertRedirect();

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->onGracePeriod())->toBeFalse();
    expect($user->subscription()?->getPlan()->getSlug())->toBe('pro');
    expect(AuditLog::where('user_id', $user->id)->where('action', 'plan_resumed')->exists())->toBeTrue();
});

test('a free user cannot cancel a paid subscription', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('billing.cancel'))
        ->assertForbidden();
});

test('invoice paid webhook stores a local invoice', function () {
    $user = User::factory()->pro()->create([
        'stripe_id' => 'cus_test_invoice',
    ]);

    $job = new ProcessStripeWebhook('invoice.paid', [
        'id' => 'in_test_paid',
        'customer' => 'cus_test_invoice',
        'amount_paid' => 19900,
        'currency' => 'uyu',
        'hosted_invoice_url' => 'https://stripe.test/invoice',
        'lines' => [
            'data' => [
                ['description' => 'Cuotas Pro'],
            ],
        ],
        'status_transitions' => [
            'paid_at' => now()->timestamp,
        ],
    ]);

    $job->handle(app(ActivatePaidPlan::class), app(PaymentGatewayManager::class));

    $invoice = Invoice::query()->where('provider_invoice_id', 'in_test_paid')->first();

    expect($invoice)->not->toBeNull();
    expect($invoice->user_id)->toBe($user->id);
    expect((string) $invoice->amount)->toBe('199.00');
    expect($invoice->status)->toBe('paid');
});

test('subscription deleted webhook downgrades the user to free', function () {
    $user = User::factory()->pro()->create([
        'stripe_id' => 'cus_test_deleted',
        'stripe_subscription_id' => 'sub_test_deleted',
        'pm_brand' => 'visa',
        'pm_last_four' => '4242',
        'pm_exp_month' => 12,
        'pm_exp_year' => 2030,
    ]);

    $job = new ProcessStripeWebhook('customer.subscription.deleted', [
        'customer' => 'cus_test_deleted',
    ]);

    $job->handle(app(ActivatePaidPlan::class), app(PaymentGatewayManager::class));

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->getPlan()->getSlug())->toBe('free');
    expect($user->stripe_subscription_id)->toBeNull();
    expect($user->pm_brand)->toBeNull();
    expect($user->pm_last_four)->toBeNull();
});

test('checkout completed webhook activates the plan from metadata', function () {
    $user = User::factory()->create();
    $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

    $job = new ProcessStripeWebhook('checkout.session.completed', [
        'metadata' => [
            'user_id' => (string) $user->id,
            'plan_slug' => $plan->getSlug(),
        ],
        'customer' => 'cus_from_checkout',
        'subscription' => 'sub_from_checkout',
    ]);

    $job->handle(app(ActivatePaidPlan::class), app(PaymentGatewayManager::class));

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->getPlan()->getSlug())->toBe('pro');
    expect($user->stripe_id)->toBe('cus_from_checkout');
    expect($user->stripe_subscription_id)->toBe('sub_from_checkout');
});
