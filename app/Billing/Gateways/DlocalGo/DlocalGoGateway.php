<?php

namespace App\Billing\Gateways\DlocalGo;

use App\Billing\Contracts\PaymentGateway;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Revoltify\Subscriptionify\Models\Plan;
use RuntimeException;

class DlocalGoGateway implements PaymentGateway
{
    /**
     * dLocal Go bills subscriptions through pre-created "Plans" (one per
     * family+interval, e.g. pro/pro-yearly/familiar/familiar-yearly) rather
     * than a dynamic checkout session. Run `php artisan dlocalgo:sync-plans`
     * once to create them and fill in the resulting tokens below.
     */
    public function checkout(User $user, Plan $plan, string $interval): string
    {
        $slug = $plan->getSlug();
        $token = config('services.dlocalgo.plan_tokens.'.$slug);

        if (! is_string($token) || $token === '') {
            throw new RuntimeException("No hay un plan de dLocal Go configurado para '{$slug}'. Corré `php artisan dlocalgo:sync-plans` y completá DLOCALGO_PLAN_TOKEN_* en .env.");
        }

        $user->forceFill(['billing_provider' => 'dlocalgo'])->save();

        return $this->checkoutBaseUrl().'/validate/subscription/'.$token.'?'.http_build_query([
            'email' => $user->email,
            'external_id' => $user->id,
        ]);
    }

    public function cancel(User $user): void
    {
        if ($user->dlocalgo_plan_id === null || $user->dlocalgo_subscription_id === null) {
            throw new RuntimeException('No tenemos el ID de suscripción de dLocal Go para este usuario; no se puede cancelar de forma remota.');
        }

        $this->client()
            ->patch("/v1/subscription/plan/{$user->dlocalgo_plan_id}/subscription/{$user->dlocalgo_subscription_id}/deactivate")
            ->throw();
    }

    /**
     * dLocal Go doesn't expose an endpoint to reactivate a deactivated
     * subscription — the customer has to subscribe again from the checkout.
     */
    public function resume(User $user): void
    {
        throw new RuntimeException('dLocal Go no permite reanudar una suscripción cancelada; el cliente tiene que suscribirse de nuevo.');
    }

    /**
     * dLocal Go notifications only carry a payment id — verify the HMAC
     * signature, then hand off to the job to fetch the real payment status.
     */
    public function handleWebhook(string $payload, string $signature): void
    {
        $apiKey = config('services.dlocalgo.api_key');
        $secretKey = config('services.dlocalgo.secret_key');

        if (! is_string($apiKey) || ! is_string($secretKey) || $apiKey === '' || $secretKey === '') {
            Log::warning('cuotas.dlocalgo.webhook_secret_missing');

            return;
        }

        if (preg_match('/Signature:\s*([a-f0-9]+)/i', $signature, $matches) !== 1) {
            Log::warning('cuotas.dlocalgo.webhook_invalid', ['reason' => 'missing_signature']);
            abort(401);
        }

        $expected = hash_hmac('sha256', $apiKey.$payload, $secretKey);

        if (! hash_equals($expected, $matches[1])) {
            Log::warning('cuotas.dlocalgo.webhook_invalid', ['reason' => 'signature_mismatch']);
            abort(401);
        }

        $data = json_decode($payload, true);
        $paymentId = is_array($data) ? ($data['payment_id'] ?? null) : null;

        if (is_string($paymentId) && $paymentId !== '') {
            ProcessDlocalGoWebhook::dispatch($paymentId);
        }
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->apiBaseUrl())
            ->acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->withToken(config('services.dlocalgo.api_key').':'.config('services.dlocalgo.secret_key'));
    }

    private function apiBaseUrl(): string
    {
        return config('services.dlocalgo.sandbox', true)
            ? 'https://api-sbx.dlocalgo.com'
            : 'https://api.dlocalgo.com';
    }

    private function checkoutBaseUrl(): string
    {
        return config('services.dlocalgo.sandbox', true)
            ? 'https://checkout-sbx.dlocalgo.com'
            : 'https://checkout.dlocalgo.com';
    }
}
