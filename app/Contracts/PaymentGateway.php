<?php

namespace App\Contracts;

use App\Models\User;
use Revoltify\Subscriptionify\Models\Plan;

interface PaymentGateway
{
    public function checkout(User $user, Plan $plan, string $interval): string;

    public function cancel(User $user): void;

    public function resume(User $user): void;

    public function handleWebhook(string $payload, string $signature): void;
}
