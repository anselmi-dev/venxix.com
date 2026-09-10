<?php

namespace App\Http\Controllers;

use App\Billing\ActivatePaidPlan;
use App\Billing\Gateways\Fake\FakePaymentGateway;
use App\Billing\PaymentGatewayManager;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Revoltify\Subscriptionify\Models\Plan;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private ActivatePaidPlan $activatePaidPlan,
        private PaymentGatewayManager $gateways,
    ) {}

    public function store(Request $request, string $plan): RedirectResponse|Response
    {
        $validated = $request->validate([
            'interval' => ['required', Rule::in(['monthly', 'yearly'])],
            'gateway' => ['required', Rule::in(['stripe', 'mercadopago', 'dlocalgo'])],
        ]);

        abort_unless(in_array($plan, ['free', 'pro', 'familiar'], true), 404);

        $interval = $validated['interval'];
        $slug = $plan === 'free'
            ? 'free'
            : ($interval === 'yearly' ? $plan.'-yearly' : $plan);

        $planModel = Plan::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $user = $request->user();

        if ($planModel->isFree()) {
            $this->activatePaidPlan->handle($user, $planModel);
            $user->forceFill(['billing_provider' => null])->save();

            return redirect()->route('billing');
        }

        $provider = $validated['gateway'];
        $gateway = $this->gateways->for($provider);
        $checkoutUrl = $gateway->checkout($user, $planModel, $interval);

        if ($gateway instanceof FakePaymentGateway) {
            $user->forceFill(['billing_provider' => $provider])->save();
        }

        // Checkout URLs point to another origin (Stripe/dLocal Go hosted
        // checkout, Mercado Pago's init_point). Inertia's <Form> submits via
        // XHR, and a plain redirect there gets the browser to try following
        // it as another XHR request — which CORS then blocks. Inertia::location
        // forces a real browser navigation instead.
        return Inertia::location($checkoutUrl);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $user = $request->user();
        $subscription = $user->subscription();

        abort_if($subscription === null || $subscription->getPlan()->isFree(), 403);

        $provider = $user->billing_provider ?? config('cuotas.billing.default_gateway');
        $planSlug = $subscription->getPlan()->getSlug();
        $this->gateways->for($provider)->cancel($user);
        $subscription->cancel();
        $user->clearSubscriptionCache();

        Log::info('cuotas.plan.cancelled', ['user_id' => $user->id]);
        AuditLog::record($user, 'plan_cancelled', ['plan' => $planSlug]);

        return back();
    }

    public function resume(Request $request): RedirectResponse
    {
        $subscription = $request->user()->subscription();

        abort_if($subscription === null || ! $subscription->onGracePeriod(), 403);

        $user = $request->user();
        $provider = $user->billing_provider ?? config('cuotas.billing.default_gateway');

        $this->gateways->for($provider)->resume($user);
        $subscription->resume();
        $user->clearSubscriptionCache();

        AuditLog::record($user, 'plan_resumed', ['plan' => $subscription->getPlan()->getSlug()]);

        return back();
    }
}
