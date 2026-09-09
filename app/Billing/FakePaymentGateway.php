<?php

namespace App\Billing;

use App\Contracts\PaymentGateway;
use App\Models\User;
use Revoltify\Subscriptionify\Models\Plan;

class FakePaymentGateway implements PaymentGateway
{
    public function __construct(private ActivatePaidPlan $activatePaidPlan) {}

    public function checkout(User $user, Plan $plan, string $interval): string
    {
        $this->activatePaidPlan->handle($user, $plan);

        return route('billing', ['checkout' => 'success']);
    }

    public function cancel(User $user): void
    {
        // Local/testing gateway has nothing remote to cancel.
    }

    public function resume(User $user): void
    {
        // Local/testing gateway has nothing remote to resume.
    }

    public function handleWebhook(string $payload, string $signature): void
    {
        //
    }
}
