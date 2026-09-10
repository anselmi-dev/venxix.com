<?php

namespace App\Billing;

use App\Billing\Contracts\PaymentGateway;
use App\Billing\Gateways\DlocalGo\DlocalGoGateway;
use App\Billing\Gateways\Fake\FakePaymentGateway;
use App\Billing\Gateways\MercadoPago\MercadoPagoGateway;
use App\Billing\Gateways\Stripe\StripeGateway;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use LogicException;

class PaymentGatewayManager
{
    public function __construct(private Application $app) {}

    public function for(string $provider): PaymentGateway
    {
        if ($this->app->environment('testing')) {
            return $this->app->make(FakePaymentGateway::class);
        }

        return match ($provider) {
            'stripe' => $this->configured(
                'services.stripe.secret',
                StripeGateway::class,
                'Stripe',
            ),
            'mercadopago' => $this->configured(
                'services.mercadopago.access_token',
                MercadoPagoGateway::class,
                'Mercado Pago',
            ),
            'dlocalgo' => $this->configured(
                'services.dlocalgo.api_key',
                DlocalGoGateway::class,
                'dLocal Go',
            ),
            default => throw new InvalidArgumentException('Gateway de pago no soportado.'),
        };
    }

    /**
     * @param  class-string<PaymentGateway>  $gateway
     */
    private function configured(string $configKey, string $gateway, string $label): PaymentGateway
    {
        $credential = config($configKey);

        if (! is_string($credential) || $credential === '') {
            if ($this->app->environment('local')) {
                return $this->app->make(FakePaymentGateway::class);
            }

            throw new LogicException($label.' no está configurado.');
        }

        return $this->app->make($gateway);
    }
}
