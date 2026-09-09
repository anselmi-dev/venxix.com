<?php

namespace App\Jobs;

use App\Billing\ActivatePaidPlan;
use App\Billing\PaymentGatewayManager;
use App\Billing\StripeGateway;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Revoltify\Subscriptionify\Models\Plan;

class ProcessStripeWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $object
     */
    public function __construct(
        public string $type,
        public array $object,
    ) {}

    public function handle(ActivatePaidPlan $activatePaidPlan, PaymentGatewayManager $gateways): void
    {
        match ($this->type) {
            'checkout.session.completed' => $this->completeCheckout($activatePaidPlan, $gateways),
            'invoice.paid' => $this->recordInvoice($gateways),
            'invoice.payment_failed' => $this->markPastDue(),
            'customer.subscription.deleted' => $this->downgradeToFree(),
            default => null,
        };
    }

    private function completeCheckout(ActivatePaidPlan $activatePaidPlan, PaymentGatewayManager $gateways): void
    {
        $user = $this->userFromObject();

        if ($user === null) {
            return;
        }

        $planSlug = $this->object['metadata']['plan_slug'] ?? null;
        $plan = is_string($planSlug) ? Plan::query()->where('slug', $planSlug)->first() : null;

        if ($plan === null) {
            return;
        }

        $customerId = is_string($this->object['customer'] ?? null) ? $this->object['customer'] : null;
        $subscriptionId = is_string($this->object['subscription'] ?? null) ? $this->object['subscription'] : null;

        $activatePaidPlan->handle($user, $plan, $customerId, $subscriptionId);
        $user->forceFill(['billing_provider' => 'stripe'])->save();

        $this->syncPaymentMethod($user, $gateways);
    }

    private function recordInvoice(PaymentGatewayManager $gateways): void
    {
        $user = $this->userFromCustomer();

        if ($user === null) {
            return;
        }

        $providerId = $this->object['id'] ?? null;

        if (! is_string($providerId)) {
            return;
        }

        $amount = isset($this->object['amount_paid']) ? ((int) $this->object['amount_paid']) / 100 : 0;
        $paidAt = isset($this->object['status_transitions']['paid_at'])
            ? Carbon::createFromTimestamp((int) $this->object['status_transitions']['paid_at'])
            : now();

        Invoice::query()->updateOrCreate(
            ['provider_invoice_id' => $providerId],
            [
                'user_id' => $user->id,
                'provider' => 'stripe',
                'description' => $this->object['lines']['data'][0]['description'] ?? 'Suscripción Cuotas',
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => strtoupper((string) ($this->object['currency'] ?? 'uyu')),
                'status' => 'paid',
                'hosted_invoice_url' => $this->object['hosted_invoice_url'] ?? null,
                'paid_at' => $paidAt,
            ],
        );

        $subscription = $user->subscription();

        if ($subscription !== null && ! $subscription->getPlan()->isFree()) {
            $subscription->renew();
            $user->clearSubscriptionCache();
        }

        $this->syncPaymentMethod($user, $gateways);
    }

    private function markPastDue(): void
    {
        $user = $this->userFromCustomer();
        $user?->subscription()?->markPastDue();
        $user?->clearSubscriptionCache();
    }

    private function downgradeToFree(): void
    {
        $user = $this->userFromCustomer();

        if ($user === null) {
            return;
        }

        $free = Plan::query()->where('slug', 'free')->first();

        if ($free === null) {
            return;
        }

        $subscription = $user->subscription();

        if ($subscription !== null && $user->canChangePlan($free)) {
            $subscription->changePlan($free, resetUsages: true);
        }

        $user->forceFill([
            'stripe_subscription_id' => null,
            'billing_provider' => null,
            'pm_brand' => null,
            'pm_last_four' => null,
            'pm_exp_month' => null,
            'pm_exp_year' => null,
        ])->save();
        $user->clearSubscriptionCache();
    }

    private function syncPaymentMethod(User $user, PaymentGatewayManager $gateways): void
    {
        $gateway = $gateways->for('stripe');

        if ($gateway instanceof StripeGateway) {
            $gateway->syncPaymentMethod($user);
        }
    }

    private function userFromObject(): ?User
    {
        $userId = $this->object['metadata']['user_id'] ?? $this->object['client_reference_id'] ?? null;

        if (is_numeric($userId)) {
            return User::query()->find((int) $userId);
        }

        return $this->userFromCustomer();
    }

    private function userFromCustomer(): ?User
    {
        $customerId = $this->object['customer'] ?? null;

        if (! is_string($customerId)) {
            return null;
        }

        return User::query()->where('stripe_id', $customerId)->first();
    }
}
