<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->id === $purchase->user_id;
    }
}
