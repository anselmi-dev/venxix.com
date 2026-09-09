<?php

namespace App\Http\Controllers;

use App\Billing\PlanAccess;
use App\Concerns\PasswordValidationRules;
use App\Http\Requests\ProfileDeleteRequest;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\AuditLog;
use App\Services\ExchangeRateResolver;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class ProfileController extends Controller
{
    use PasswordValidationRules;

    public function show(
        Request $request,
        PlanAccess $planAccess,
        ExchangeRateResolver $exchangeRateResolver,
    ): Response {
        $user = $request->user();
        $plan = $user->subscription()?->getPlan();
        $family = $planAccess->family($user);
        $exchangeRate = $exchangeRateResolver->forUser($user);

        $props = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'preferred_currency' => $user->preferred_currency->value,
                'show_both_currencies' => $user->show_both_currencies,
                'reminders_enabled' => $user->reminders_enabled,
                'locale' => $user->locale,
                'usd_uyu_rate' => $exchangeRate['rate'],
                'exchange_rate_automatic' => $exchangeRate['automatic'],
                'exchange_rate_date' => $exchangeRate['date'],
                'exchange_rate_source' => $exchangeRate['source'],
            ],
            'plan' => [
                'name' => $plan?->getName() ?? __('ui.common.free'),
                'family' => $family,
                'price' => (string) config('cuotas.prices.'.$family.'.monthly'),
                'is_free' => $plan?->isFree() ?? true,
            ],
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'twoFactorEnabled' => Features::canManageTwoFactorAuthentication()
                ? $user->hasEnabledTwoFactorAuthentication()
                : false,
            'features' => $planAccess->features($user),
            'push' => [
                'enabled' => app(PushNotificationService::class)->isConfigured(),
                'public_key' => config('push.vapid.public_key'),
            ],
        ];

        return Inertia::render('Profile/Index', $props);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back();
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        AuditLog::record($request->user(), 'password_changed');

        return back();
    }

    public function updateCurrency(
        UpdateCurrencyRequest $request,
        PlanAccess $planAccess,
    ): RedirectResponse {
        $user = $request->user();

        $user->update([
            'preferred_currency' => $request->validated('preferred_currency'),
            'show_both_currencies' => $request->boolean('show_both_currencies'),
            'usd_uyu_rate' => $planAccess->hasAutomaticExchangeRate($user)
                ? $user->usd_uyu_rate
                : $request->validated('usd_uyu_rate'),
        ]);

        return back();
    }

    public function updateNotifications(
        UpdateNotificationPreferencesRequest $request,
        PlanAccess $planAccess,
    ): RedirectResponse {
        abort_unless($planAccess->hasReminders($request->user()), 403);

        $request->user()->update([
            'reminders_enabled' => $request->boolean('reminders_enabled'),
        ]);

        return back();
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
