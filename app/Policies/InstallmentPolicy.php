<?php

namespace App\Policies;

use App\Models\Installment;
use App\Models\User;

class InstallmentPolicy
{
    public function update(User $user, Installment $installment): bool
    {
        return $user->id === $installment->user_id;
    }
}
