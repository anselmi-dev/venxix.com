<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function start(User $user, ImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->start($user);

        return redirect()->route('panel');
    }

    public function stop(ImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->stop();

        return redirect('/admin');
    }
}
