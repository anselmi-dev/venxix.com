<?php

namespace App\Billing;

use App\Contracts\PaymentGateway;
use App\Jobs\ProcessStripeWebhook;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Revoltify\Subscriptionify\Models\Plan;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeGateway implements PaymentGateway
{
    public function checkout(User $user, Plan $plan, string $interval): string
    {
        $family = str_starts_with($plan->getSlug(), 'familiar') ? 'familiar' : 'pro';
        $amount = (int) config('cuotas.prices.'.$family.'.'.$interval);
        $stripe = $this->client();

        $customerId = $user->stripe_id;

        if ($customerId === null) {
            $customer = $stripe->customers->create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => ['user_id' => (string) $user->id],
            ]);
            $customerId = $customer->id;
            $user->forceFill(['stripe_id' => $customerId])->save();
        }

        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'success_url' => route('billing', ['checkout' => 'success'], true),
            'cancel_url' => route('pricing', absolute: true),
            'client_reference_id' => (string) $user->id,
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_slug' => $plan->getSlug(),
            ],
            'subscription_data' => [
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'plan_slug' => $plan->getSlug(),
                ],
                ...($plan->getTrialDays() > 0 ? ['trial_period_days' => $plan->getTrialDays()] : []),
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'uyu',
                    'unit_amount' => $amount * 100,
                    'recurring' => [
                        'interval' => $interval === 'yearly' ? 'year' : 'month',
                    ],
                    'product_data' => [
                        'name' => 'Cuotas '.$plan->getName(),
                    ],
                ],
            ]],
        ]);

        return $session->url ?? route('billing');
    }

    public function cancel(User $user): void
    {
        if ($user->stripe_subscription_id === null) {
            return;
        }

        $this->client()->subscriptions->update($user->stripe_subscription_id, [
            'cancel_at_period_end' => true,
        ]);
    }

    public function resume(User $user): void
    {
        if ($user->stripe_subscription_id === null) {
            return;
        }

        $this->client()->subscriptions->update($user->stripe_subscription_id, [
            'cancel_at_period_end' => false,
        ]);
    }

    /**
     * Fetch the card on file for the user's Stripe customer and store its
     * display details (brand/last 4/expiry) — never the PAN itself.
     */
    public function syncPaymentMethod(User $user): void
    {
        if ($user->stripe_id === null) {
            return;
        }

        try {
            $paymentMethods = $this->client()->paymentMethods->all([
                'customer' => $user->stripe_id,
                'type' => 'card',
                'limit' => 1,
            ]);
        } catch (ApiErrorException $e) {
            Log::warning('cuotas.stripe.payment_method_sync_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $card = $paymentMethods->data[0]->card ?? null;

        if ($card === null) {
            return;
        }

        $user->forceFill([
            'pm_brand' => $card->brand,
            'pm_last_four' => $card->last4,
            'pm_exp_month' => $card->exp_month,
            'pm_exp_year' => $card->exp_year,
        ])->save();
    }

    public function handleWebhook(string $payload, string $signature): void
    {
        $secret = config('services.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            Log::warning('cuotas.stripe.webhook_secret_missing');

            return;
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('cuotas.stripe.webhook_invalid', ['message' => $e->getMessage()]);

            abort(400);
        }

        /** @var array<string, mixed> $object */
        $object = $event->data->object->toArray();

        ProcessStripeWebhook::dispatch($event->type, $object);
    }

    private function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }
}
