<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('dlocalgo:sync-plans')]
#[Description('Crea (o actualiza) los planes de suscripción de dLocal Go y muestra sus tokens')]
class DlocalGoSyncPlans extends Command
{
    /**
     * @var array<string, array{name: string, family: string, interval: 'monthly'|'yearly'}>
     */
    private const PLANS = [
        'pro' => ['name' => 'Cuotas Pro', 'family' => 'pro', 'interval' => 'monthly'],
        'pro-yearly' => ['name' => 'Cuotas Pro Anual', 'family' => 'pro', 'interval' => 'yearly'],
        'familiar' => ['name' => 'Cuotas Familiar', 'family' => 'familiar', 'interval' => 'monthly'],
        'familiar-yearly' => ['name' => 'Cuotas Familiar Anual', 'family' => 'familiar', 'interval' => 'yearly'],
    ];

    public function handle(): int
    {
        $apiKey = config('services.dlocalgo.api_key');
        $secretKey = config('services.dlocalgo.secret_key');

        if (! is_string($apiKey) || ! is_string($secretKey) || $apiKey === '' || $secretKey === '') {
            $this->error('Configurá DLOCALGO_API_KEY y DLOCALGO_SECRET_KEY en .env antes de correr esto.');

            return self::FAILURE;
        }

        $baseUrl = config('services.dlocalgo.sandbox', true)
            ? 'https://api-sbx.dlocalgo.com'
            : 'https://api.dlocalgo.com';

        $this->line('Creando planes en dLocal Go ('.($this->isSandbox() ? 'sandbox' : 'producción').')...');
        $this->newLine();

        $envLines = [];

        foreach (self::PLANS as $slug => $definition) {
            $amount = config('cuotas.prices.'.$definition['family'].'.'.$definition['interval']);

            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->withToken($apiKey.':'.$secretKey)
                ->post('/v1/subscription/plan', [
                    'name' => $definition['name'],
                    'description' => $definition['name'],
                    'country' => 'UY',
                    'currency' => 'UYU',
                    'amount' => $amount,
                    'frequency_type' => $definition['interval'] === 'yearly' ? 'YEARLY' : 'MONTHLY',
                    'frequency_value' => 1,
                    'notification_url' => route('dlocalgo.webhook', absolute: true),
                    'success_url' => route('billing', ['checkout' => 'success'], true),
                    'back_url' => route('pricing', absolute: true),
                ]);

            if ($response->failed()) {
                $this->error("No se pudo crear el plan '{$slug}': ".$response->body());

                continue;
            }

            $token = $response->json('plan_token');
            $envKey = 'DLOCALGO_PLAN_TOKEN_'.strtoupper(str_replace('-', '_', $slug));

            $this->info("{$definition['name']} ({$slug}): {$token}");
            $envLines[] = $envKey.'='.$token;
        }

        if ($envLines === []) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Agregá estas líneas a tu .env:');
        $this->newLine();

        foreach ($envLines as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }

    private function isSandbox(): bool
    {
        return (bool) config('services.dlocalgo.sandbox', true);
    }
}
