<?php

namespace App\Http\Controllers;

use App\Billing\PlanAccess;
use App\Models\Card;
use App\Services\ExchangeRateResolver;
use App\Services\InstallmentProjectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SummaryController extends Controller
{
    public function __construct(
        private InstallmentProjectionService $projection,
        private ExchangeRateResolver $exchangeRateResolver,
        private PlanAccess $planAccess,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $exchangeRate = $this->exchangeRateResolver->forUser($user);
        $cards = $user->cards()->orderBy('bank')->get();
        $selectedCardId = $request->integer('card') ?: null;
        $selectedCard = $selectedCardId !== null
            ? $cards->firstWhere('id', $selectedCardId)
            : null;

        if ($selectedCardId !== null && $selectedCard === null) {
            abort(404);
        }

        return Inertia::render('Summary/Index', [
            'cards' => $cards->map(fn (Card $card) => [
                'id' => $card->id,
                'ulid' => $card->ulid,
                'display_name' => $card->displayName(),
                'last_four' => $card->last_four,
            ])->values(),
            'selectedCardId' => $selectedCard?->id,
            'selectedCardUlid' => $selectedCard?->ulid,
            'projection' => $this->projection->forUser($user, $selectedCard?->id),
            'showBothCurrencies' => $user->show_both_currencies,
            'preferredCurrency' => $user->preferred_currency->value,
            'exchangeRate' => $exchangeRate['rate'],
            'canExport' => $this->planAccess->canExport($user),
        ]);
    }
}
