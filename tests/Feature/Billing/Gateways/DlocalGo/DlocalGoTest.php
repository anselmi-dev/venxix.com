<?php

use App\Billing\ActivatePaidPlan;
use App\Billing\Gateways\DlocalGo\DlocalGoGateway;
use App\Billing\Gateways\DlocalGo\ProcessDlocalGoWebhook;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Revoltify\Subscriptionify\Models\Plan;

test('dLocal Go checkout builds the hosted subscription URL', function () {
    config([
        'services.dlocalgo.sandbox' => true,
        'services.dlocalgo.plan_tokens.pro' => 'plan-token-123',
    ]);

    $user = User::factory()->create();
    $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

    $url = app(DlocalGoGateway::class)->checkout($user, $plan, 'monthly');

    expect($url)->toStartWith('https://checkout-sbx.dlocalgo.com/validate/subscription/plan-token-123?');
    expect($url)->toContain('external_id='.$user->id);
    expect($user->fresh()->billing_provider)->toBe('dlocalgo');
});

test('dLocal Go checkout fails clearly when no plan token is configured', function () {
    config(['services.dlocalgo.plan_tokens.pro' => null]);

    $user = User::factory()->create();
    $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

    app(DlocalGoGateway::class)->checkout($user, $plan, 'monthly');
})->throws(RuntimeException::class);

test('dLocal Go rejects webhooks with an invalid signature', function () {
    config([
        'services.dlocalgo.api_key' => 'api-key',
        'services.dlocalgo.secret_key' => 'secret-key',
    ]);

    $this->postJson(route('dlocalgo.webhook'), ['payment_id' => 'DP-1'], [
        'Authorization' => 'V2-HMAC-SHA256, Signature: invalid',
    ])->assertUnauthorized();
});

test('dLocal Go accepts signed webhooks and queues processing', function () {
    Bus::fake();
    config([
        'services.dlocalgo.api_key' => 'api-key',
        'services.dlocalgo.secret_key' => 'secret-key',
    ]);

    $payload = ['payment_id' => 'DP-1'];
    $hash = hash_hmac('sha256', 'api-key'.json_encode($payload), 'secret-key');

    $this->postJson(route('dlocalgo.webhook'), $payload, [
        'Authorization' => 'V2-HMAC-SHA256, Signature: '.$hash,
    ])->assertNoContent();

    Bus::assertDispatched(
        ProcessDlocalGoWebhook::class,
        fn (ProcessDlocalGoWebhook $job) => $job->paymentId === 'DP-1',
    );
});

test('a paid dLocal Go subscription payment activates the selected plan', function () {
    config([
        'services.dlocalgo.api_key' => 'api-key',
        'services.dlocalgo.secret_key' => 'secret-key',
        'services.dlocalgo.sandbox' => true,
    ]);

    $user = User::factory()->create();

    Http::fake([
        'api-sbx.dlocalgo.com/v1/payments/DP-1' => Http::response([
            'id' => 'DP-1',
            'status' => 'PAID',
            'amount' => 199,
            'currency' => 'UYU',
            'external_id' => (string) $user->id,
            'payer' => ['email' => $user->email],
        ]),
    ]);

    $job = new ProcessDlocalGoWebhook('DP-1');
    $job->handle(app(ActivatePaidPlan::class));

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->getPlan()->getSlug())->toBe('pro');
    expect($user->billing_provider)->toBe('dlocalgo');

    $invoice = Invoice::query()->where('provider_invoice_id', 'dlocalgo:DP-1')->first();
    expect($invoice)->not->toBeNull();
    expect((string) $invoice->amount)->toBe('199.00');
});

test('a renewal dLocal Go payment records an invoice without reactivating the plan', function () {
    config([
        'services.dlocalgo.api_key' => 'api-key',
        'services.dlocalgo.secret_key' => 'secret-key',
        'services.dlocalgo.sandbox' => true,
    ]);

    $user = User::factory()->pro()->create([
        'billing_provider' => 'dlocalgo',
        'dlocalgo_subscription_id' => 'sub-1',
        'dlocalgo_plan_id' => 'plan-1',
    ]);

    Http::fake([
        'api-sbx.dlocalgo.com/v1/payments/DP-2' => Http::response([
            'id' => 'DP-2',
            'status' => 'PAID',
            'amount' => 199,
            'currency' => 'UYU',
            'external_id' => (string) $user->id,
            'payer' => ['email' => $user->email],
        ]),
    ]);

    $job = new ProcessDlocalGoWebhook('DP-2');
    $job->handle(app(ActivatePaidPlan::class));

    $user->refresh()->clearSubscriptionCache();

    expect($user->subscription()?->getPlan()->getSlug())->toBe('pro');
    expect(Invoice::query()->where('provider_invoice_id', 'dlocalgo:DP-2')->exists())->toBeTrue();
});

test('unpaid dLocal Go payments are ignored', function () {
    config([
        'services.dlocalgo.api_key' => 'api-key',
        'services.dlocalgo.secret_key' => 'secret-key',
        'services.dlocalgo.sandbox' => true,
    ]);

    $user = User::factory()->create();

    Http::fake([
        'api-sbx.dlocalgo.com/v1/payments/DP-3' => Http::response([
            'id' => 'DP-3',
            'status' => 'REJECTED',
            'amount' => 199,
            'currency' => 'UYU',
            'external_id' => (string) $user->id,
        ]),
    ]);

    $job = new ProcessDlocalGoWebhook('DP-3');
    $job->handle(app(ActivatePaidPlan::class));

    expect(Invoice::query()->where('provider_invoice_id', 'dlocalgo:DP-3')->exists())->toBeFalse();
    expect($user->fresh()->billing_provider)->toBeNull();
});
