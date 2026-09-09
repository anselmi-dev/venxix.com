<?php

namespace App\Billing;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Revoltify\Subscriptionify\Models\Plan;

class ActivatePaidPlan
{
    public function handle(User $user, Plan $plan, ?string $stripeCustomerId = null, ?string $stripeSubscriptionId = null): void
    {
        if ($stripeCustomerId !== null) {
            $user->forceFill(['stripe_id' => $stripeCustomerId])->save();
        }

        if ($stripeSubscriptionId !== null) {
            $user->forceFill(['stripe_subscription_id' => $stripeSubscriptionId])->save();
        }

        $subscription = $user->subscription();

        if ($subscription === null) {
            $user->subscribe($plan);
        } elseif ($user->canChangePlan($plan)) {
            $subscription->changePlan($plan, resetUsages: true);
            $user->clearSubscriptionCache();
        }

        Log::info('cuotas.plan.changed', [
            'user_id' => $user->id,
            'plan' => $plan->getSlug(),
        ]);

        AuditLog::record($user, 'plan_changed', ['plan' => $plan->getSlug()]);
    }
}
