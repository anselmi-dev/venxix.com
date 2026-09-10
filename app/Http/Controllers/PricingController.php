<?php

namespace App\Http\Controllers;

use App\Billing\PlanAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Revoltify\Subscriptionify\Models\Plan;

class PricingController extends Controller
{
    public function __invoke(Request $request, PlanAccess $planAccess): Response
    {
        $user = $request->user();
        $hasConfiguredGateway = filled(config('services.stripe.secret'))
            || filled(config('services.mercadopago.access_token'))
            || filled(config('services.dlocalgo.api_key'));
        $showUnconfiguredGateways = app()->environment(['local', 'testing'])
            && ! $hasConfiguredGateway;
        $gateways = collect([
            'stripe' => [
                'slug' => 'stripe',
                'name' => __('ui.pricing.gateway_stripe'),
                'configured' => filled(config('services.stripe.secret')),
            ],
            'mercadopago' => [
                'slug' => 'mercadopago',
                'name' => __('ui.pricing.gateway_mercadopago'),
                'configured' => filled(config('services.mercadopago.access_token')),
            ],
            'dlocalgo' => [
                'slug' => 'dlocalgo',
                'name' => __('ui.pricing.gateway_dlocalgo'),
                'configured' => filled(config('services.dlocalgo.api_key')),
            ],
        ])->filter(fn (array $gateway) => $showUnconfiguredGateways || $gateway['configured'])->values();

        return Inertia::render('Pricing', [
            'authenticated' => $user !== null,
            'currentFamily' => $user ? $planAccess->family($user) : null,
            'prices' => config('cuotas.prices'),
            'paymentGateways' => $gateways,
            'defaultGateway' => config('cuotas.billing.default_gateway'),
            'plans' => Plan::query()
                ->whereIn('slug', ['free', 'pro', 'familiar'])
                ->orderBy('sort_order')
                ->get(['name', 'slug', 'description', 'is_free', 'trial_days'])
                ->map(fn (Plan $plan) => [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'is_free' => $plan->is_free,
                    'trial_days' => $plan->trial_days,
                ]),
        ]);
    }
}
