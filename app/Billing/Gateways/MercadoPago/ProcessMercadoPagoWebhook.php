<?php

namespace App\Billing\Gateways\MercadoPago;

use App\Billing\ActivatePaidPlan;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Plan;

class ProcessMercadoPagoWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 30, 120];

    public function __construct(
        public string $type,
        public string $resourceId,
    ) {}

    public function handle(ActivatePaidPlan $activatePaidPlan): void
    {
        match ($this->type) {
            'subscription_preapproval' => $this->processSubscription($activatePaidPlan),
            'subscription_authorized_payment' => $this->processAuthorizedPayment(),
            default => null,
        };
    }

    private function processSubscription(ActivatePaidPlan $activatePaidPlan): void
    {
        $subscription = $this->client()
            ->get('/preapproval/'.$this->resourceId)
            ->throw()
            ->json();

        if (! is_array($subscription)) {
            return;
        }

        $reference = $this->parseReference($subscription['external_reference'] ?? null);
        $user = $reference === null ? null : User::query()->find($reference['user_id']);

        if ($user === null) {
            return;
        }

        $status = $subscription['status'] ?? null;

        if ($status === 'authorized') {
            $plan = Plan::query()->where('slug', $reference['plan_slug'])->first();

            if ($plan === null) {
                return;
            }

            $activatePaidPlan->handle($user, $plan);
            $user->forceFill([
                'billing_provider' => 'mercadopago',
                'mercadopago_subscription_id' => $this->resourceId,
            ])->save();

            return;
        }

        if (in_array($status, ['cancelled', 'paused'], true)) {
            $this->cancelLocalSubscription($user);
        }
    }

    private function processAuthorizedPayment(): void
    {
        $payment = $this->client()
            ->get('/authorized_payments/'.$this->resourceId)
            ->throw()
            ->json();

        if (! is_array($payment) || ($payment['status'] ?? null) !== 'approved') {
            return;
        }

        $reference = $this->parseReference($payment['external_reference'] ?? null);

        if ($reference === null && is_string($payment['preapproval_id'] ?? null)) {
            $subscription = $this->client()
                ->get('/preapproval/'.$payment['preapproval_id'])
                ->throw()
                ->json();
            $reference = is_array($subscription)
                ? $this->parseReference($subscription['external_reference'] ?? null)
                : null;
        }

        $user = $reference === null ? null : User::query()->find($reference['user_id']);

        if ($user === null) {
            return;
        }

        $providerId = is_scalar($payment['id'] ?? null)
            ? 'mp:'.(string) $payment['id']
            : 'mp:'.$this->resourceId;

        Invoice::query()->updateOrCreate(
            ['provider_invoice_id' => $providerId],
            [
                'user_id' => $user->id,
                'provider' => 'mercadopago',
                'description' => 'Suscripción Cuotas',
                'amount' => number_format((float) ($payment['transaction_amount'] ?? 0), 2, '.', ''),
                'currency' => strtoupper((string) ($payment['currency_id'] ?? 'UYU')),
                'status' => 'paid',
                'hosted_invoice_url' => null,
                'paid_at' => isset($payment['date_approved'])
                    ? Carbon::parse($payment['date_approved'])
                    : now(),
            ],
        );

        $subscription = $user->subscription();

        if ($subscription !== null && ! $subscription->getPlan()->isFree()) {
            $subscription->renew();
            $user->clearSubscriptionCache();
        }
    }

    /**
     * @return array{user_id: int, plan_slug: string}|null
     */
    private function parseReference(mixed $reference): ?array
    {
        if (! is_string($reference)
            || preg_match('/^user:(\d+);plan:([a-z0-9-]+)$/', $reference, $matches) !== 1) {
            return null;
        }

        return [
            'user_id' => (int) $matches[1],
            'plan_slug' => $matches[2],
        ];
    }

    private function cancelLocalSubscription(User $user): void
    {
        $subscription = $user->subscription();

        if ($subscription !== null
            && ! $subscription->getPlan()->isFree()
            && $subscription->getStatus() !== SubscriptionStatus::Cancelled) {
            $subscription->cancel();
        }

        $user->clearSubscriptionCache();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl('https://api.mercadopago.com')
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->withToken(config('services.mercadopago.access_token'));
    }
}
