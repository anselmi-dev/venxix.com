<?php

namespace App\Billing;

use App\Contracts\PaymentGateway;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LogicException;
use Revoltify\Subscriptionify\Models\Plan;
use RuntimeException;

class MercadoPagoGateway implements PaymentGateway
{
    public function checkout(User $user, Plan $plan, string $interval): string
    {
        $family = str_starts_with($plan->getSlug(), 'familiar') ? 'familiar' : 'pro';
        $amount = (int) config('cuotas.prices.'.$family.'.'.$interval);
        $idempotencyKey = (string) Str::uuid();

        $response = $this->client()
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->post('/preapproval', [
                'reason' => 'Cuotas '.$plan->getName(),
                'external_reference' => $this->externalReference($user, $plan),
                'payer_email' => $user->email,
                'back_url' => route('billing', ['checkout' => 'success'], true),
                'status' => 'pending',
                'auto_recurring' => [
                    'frequency' => $interval === 'yearly' ? 12 : 1,
                    'frequency_type' => 'months',
                    'transaction_amount' => $amount,
                    'currency_id' => 'UYU',
                    ...($plan->getTrialDays() > 0 ? [
                        'free_trial' => [
                            'frequency' => $plan->getTrialDays(),
                            'frequency_type' => 'days',
                        ],
                    ] : []),
                ],
            ])
            ->throw();

        $subscriptionId = $response->json('id');
        $checkoutUrl = $response->json('init_point');

        if (! is_string($subscriptionId) || ! is_string($checkoutUrl)) {
            throw new RuntimeException('Mercado Pago no devolvió una URL de checkout válida.');
        }

        $user->forceFill([
            'billing_provider' => 'mercadopago',
            'mercadopago_subscription_id' => $subscriptionId,
        ])->save();

        return $checkoutUrl;
    }

    public function cancel(User $user): void
    {
        if ($user->mercadopago_subscription_id === null) {
            return;
        }

        $this->client()
            ->put('/preapproval/'.$user->mercadopago_subscription_id, [
                'status' => 'cancelled',
            ])
            ->throw();
    }

    public function resume(User $user): void
    {
        if ($user->mercadopago_subscription_id === null) {
            return;
        }

        $this->client()
            ->put('/preapproval/'.$user->mercadopago_subscription_id, [
                'status' => 'authorized',
            ])
            ->throw();
    }

    /**
     * Mercado Pago's signature scheme needs the `x-request-id` header and the
     * `data.id` query param alongside `x-signature`, which don't fit the
     * (payload, signature) shape this contract method offers. Verification and
     * dispatch for Mercado Pago webhooks live in MercadoPagoWebhookController
     * instead, so this method is intentionally unreachable.
     */
    public function handleWebhook(string $payload, string $signature): void
    {
        throw new LogicException('Los webhooks de Mercado Pago se procesan en MercadoPagoWebhookController, no en el gateway.');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl('https://api.mercadopago.com')
            ->acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->withToken(config('services.mercadopago.access_token'));
    }

    private function externalReference(User $user, Plan $plan): string
    {
        return 'user:'.$user->id.';plan:'.$plan->getSlug();
    }
}
