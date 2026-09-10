<?php

namespace App\Billing\Gateways\DlocalGo;

use App\Billing\ActivatePaidPlan;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Revoltify\Subscriptionify\Models\Plan;

class ProcessDlocalGoWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $paymentId,
    ) {}

    public function handle(ActivatePaidPlan $activatePaidPlan): void
    {
        $payment = $this->client()
            ->get('/v1/payments/'.$this->paymentId)
            ->throw()
            ->json();

        if (! is_array($payment) || ($payment['status'] ?? null) !== 'PAID') {
            return;
        }

        $user = $this->resolveUser($payment);

        if ($user === null) {
            Log::warning('cuotas.dlocalgo.webhook_user_not_found', ['payment_id' => $this->paymentId]);

            return;
        }

        $isFirstPayment = $user->dlocalgo_subscription_id === null;

        // Best-effort: capture the subscription/plan ids if dLocal Go includes
        // them on the payment payload, needed later to cancel the subscription.
        $subscriptionId = $payment['subscription_id'] ?? $payment['subscription']['id'] ?? null;
        $planId = $payment['plan_id'] ?? $payment['subscription']['plan_id'] ?? null;

        if (is_scalar($subscriptionId) || is_scalar($planId)) {
            $user->forceFill([
                'dlocalgo_subscription_id' => is_scalar($subscriptionId) ? (string) $subscriptionId : $user->dlocalgo_subscription_id,
                'dlocalgo_plan_id' => is_scalar($planId) ? (string) $planId : $user->dlocalgo_plan_id,
            ])->save();
        }

        if ($isFirstPayment) {
            $plan = $this->resolvePlan($payment);

            if ($plan === null) {
                Log::warning('cuotas.dlocalgo.webhook_plan_not_found', ['payment_id' => $this->paymentId]);

                return;
            }

            $activatePaidPlan->handle($user, $plan);
            $user->forceFill(['billing_provider' => 'dlocalgo'])->save();
        }

        Invoice::query()->updateOrCreate(
            ['provider_invoice_id' => 'dlocalgo:'.$this->paymentId],
            [
                'user_id' => $user->id,
                'provider' => 'dlocalgo',
                'description' => 'Suscripción Cuotas',
                'amount' => number_format((float) ($payment['amount'] ?? 0), 2, '.', ''),
                'currency' => strtoupper((string) ($payment['currency'] ?? 'UYU')),
                'status' => 'paid',
                'hosted_invoice_url' => null,
                'paid_at' => isset($payment['approved_date'])
                    ? Carbon::parse($payment['approved_date'])
                    : now(),
            ],
        );

        if (! $isFirstPayment) {
            $subscription = $user->subscription();

            if ($subscription !== null && ! $subscription->getPlan()->isFree()) {
                $subscription->renew();
                $user->clearSubscriptionCache();
            }
        }
    }

    /**
     * dLocal Go's payment payload doesn't document a merchant reference field
     * explicitly; this checks every plausible field before giving up, in
     * order of reliability. Verify the actual sandbox payload shape and
     * adjust if needed before relying on this in production.
     *
     * @param  array<string, mixed>  $payment
     */
    private function resolveUser(array $payment): ?User
    {
        $externalId = $payment['external_id']
            ?? $payment['payer']['user_reference']
            ?? null;

        if (is_numeric($externalId)) {
            $user = User::query()->find((int) $externalId);

            if ($user !== null) {
                return $user;
            }
        }

        $email = $payment['payer']['email'] ?? null;

        if (is_string($email) && $email !== '') {
            return User::query()->where('email', $email)->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function resolvePlan(array $payment): ?Plan
    {
        $amount = (float) ($payment['amount'] ?? 0);

        foreach (config('cuotas.prices') as $family => $intervals) {
            if ($family === 'free') {
                continue;
            }

            foreach ($intervals as $interval => $price) {
                if ((float) $price !== $amount) {
                    continue;
                }

                $slug = $interval === 'yearly' ? $family.'-yearly' : $family;

                return Plan::query()->where('slug', $slug)->first();
            }
        }

        return null;
    }

    private function client(): PendingRequest
    {
        $sandbox = config('services.dlocalgo.sandbox', true);

        return Http::baseUrl($sandbox ? 'https://api-sbx.dlocalgo.com' : 'https://api.dlocalgo.com')
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->withToken(config('services.dlocalgo.api_key').':'.config('services.dlocalgo.secret_key'));
    }
}
