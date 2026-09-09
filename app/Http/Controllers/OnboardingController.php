<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function welcome(): Response|RedirectResponse
    {
        if (auth()->user()->hasCompletedOnboarding()) {
            return redirect()->route('panel');
        }

        return Inertia::render('Onboarding/Welcome', [
            'name' => auth()->user()->name,
        ]);
    }

    public function skip(): RedirectResponse
    {
        auth()->user()->update([
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route('panel');
    }

    public function cardForm(): Response
    {
        return Inertia::render('Onboarding/Card');
    }

    public function storeCard(StoreCardRequest $request): RedirectResponse
    {
        $request->user()->cards()->create($request->validated());

        return redirect()->route('onboarding.currency');
    }

    public function skipCard(): RedirectResponse
    {
        return redirect()->route('onboarding.currency');
    }

    public function currencyForm(): Response
    {
        return Inertia::render('Onboarding/Currency', [
            'preferredCurrency' => auth()->user()->preferred_currency->value,
            'showBothCurrencies' => auth()->user()->show_both_currencies,
        ]);
    }

    public function storeCurrency(UpdateCurrencyRequest $request): RedirectResponse
    {
        $request->user()->update([
            'preferred_currency' => $request->validated('preferred_currency'),
            'show_both_currencies' => $request->boolean('show_both_currencies'),
            'usd_uyu_rate' => $request->validated('usd_uyu_rate'),
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route('panel');
    }
}
