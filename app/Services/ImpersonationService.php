<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public function start(User $target): void
    {
        /** @var User|null $admin */
        $admin = Auth::user();

        abort_unless($admin?->is_admin, 403);
        abort_if($target->is_admin, 403, 'No se puede impersonar a otro administrador.');

        session(['impersonate_admin_id' => $admin->id]);
        Auth::login($target);
    }

    public function stop(): void
    {
        $adminId = session('impersonate_admin_id');

        abort_unless($adminId, 403);

        session()->forget('impersonate_admin_id');
        Auth::loginUsingId($adminId);
    }

    public function isImpersonating(): bool
    {
        return session()->has('impersonate_admin_id');
    }
}
