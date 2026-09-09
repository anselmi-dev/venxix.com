<?php

use App\Billing\ActivatePaidPlan;
use App\Billing\MercadoPagoGateway;
use App\Jobs\ProcessMercadoPagoWebhook;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Revoltify\Subscriptionify\Models\Plan;

test('Mercado Pago checkout creates a pending subscription', function () {
    config(['services.mercadopago.access_token' => 'TEST-token']);
    Http::fake([
        'api.mercadopago.com/preapproval' => Http::response([
            'id' => 'preapproval_123',
            'init_point' => 'https://mercadopago.test/checkout',
            'status' => 'pending',
        ], 201),
    ]);

    $user = User::factory()->create();
    $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

    $url = app(MercadoPagoGateway::class)->checkout($user, $plan, 'monthly');

    expect($url)->toBe('https://mercadopago.test/checkout');
    expect($user->fresh())
        ->billing_provider->toBe('mercadopago')
        ->mercadopago_subscription_id->toBe('preapproval_123');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/preapproval'
        && $request['external_reference'] === 'user:'.$user->id.';plan:pro'
        && $request['auto_recurring']['currency_id'] === 'UYU'
        && $request->hasHeader('X-Idempotency-Key'));
});

test('Mercado Pago rejects webhooks with an invalid signature', function () {
    config(['services.mercadopago.webhook_secret' => 'webhook-secret']);

    $this->postJson(route('mercadopago.webhook', ['data_id' => '123']), [
        'type' => 'subscription_preapproval',
        'data' => ['id' => '123'],
    ], [
        'x-signature' => 'ts=123,v1=invalid',
        'x-request-id' => 'request-123',
    ])->assertUnauthorized();
});

test('Mercado Pago accepts signed webhooks and queues processing', function () {
    Bus::fake();
    config(['services.mercadopago.webhook_secret' => 'webhook-secret']);

    $manifest = 'id:123;request-id:request-123;ts:456;';
    $hash = hash_hmac('sha256', $manifest, 'webhook-secret');

    $this->postJson(route('mercadopago.webhook', ['data_id' => '123']), [
        'type' => 'subscription_preapproval',
        'data' => ['id' => '123'],
    ], [
        'x-signature' => 'ts=456,v1='.$hash,
        'x-request-id' => 'request-123',
    ])->assertOk();

    Bus::assertDispatched(
        ProcessMercadoPagoWebhook::class,
        fn (ProcessMercadoPagoWebhook $job) => $job->type === 'subscription_preapproval'
            && $job->resourceId === '123',
    );
});

test('authorized Mercado Pago subscriptions activate the selected plan', function () {
    config(['services.mercadopago.access_token' => 'TEST-token']);

    $user = User::factory()->create();
    Http::fake([
        'api.mercadopago.com/preapproval/preapproval_123' => Http::response([
            'id' => 'preapproval_123',
            'status' => 'authorized',
            'external_reference' => 'user:'.$user->id.';plan:pro',
        ]),
    ]);

    $job = new ProcessMercadoPagoWebhook(
        'subscription_preapproval',
        'preapproval_123',
    );
    $job->handle(app(ActivatePaidPlan::class));

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->getPlan()->getSlug())->toBe('pro');
    expect($user)
        ->billing_provider->toBe('mercadopago')
        ->mercadopago_subscription_id->toBe('preapproval_123');
});

test('cancelled Mercado Pago subscriptions keep local access during grace period', function () {
    config(['services.mercadopago.access_token' => 'TEST-token']);

    $user = User::factory()->pro()->create([
        'billing_provider' => 'mercadopago',
        'mercadopago_subscription_id' => 'preapproval_123',
    ]);
    Http::fake([
        'api.mercadopago.com/preapproval/preapproval_123' => Http::response([
            'id' => 'preapproval_123',
            'status' => 'cancelled',
            'external_reference' => 'user:'.$user->id.';plan:pro',
        ]),
    ]);

    $job = new ProcessMercadoPagoWebhook(
        'subscription_preapproval',
        'preapproval_123',
    );
    $job->handle(app(ActivatePaidPlan::class));

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->getPlan()->getSlug())->toBe('pro');
    expect($user->subscription()?->onGracePeriod())->toBeTrue();
});
