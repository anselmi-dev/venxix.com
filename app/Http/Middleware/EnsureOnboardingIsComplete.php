<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsComplete
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($request->routeIs('onboarding.*', 'logout')) {
            if ($user->hasCompletedOnboarding() && $request->routeIs('onboarding.*')) {
                return redirect()->route('panel');
            }

            return $next($request);
        }

        if (! $user->hasCompletedOnboarding()) {
            return redirect()->route('onboarding.welcome');
        }

        return $next($request);
    }
}
