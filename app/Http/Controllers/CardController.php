<?php

namespace App\Http\Controllers;

use App\Billing\PlanAccess;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Models\Card;
use App\Services\ExchangeRateResolver;
use App\Services\InstallmentProjectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    public function __construct(
        private InstallmentProjectionService $projection,
        private PlanAccess $planAccess,
        private ExchangeRateResolver $exchangeRateResolver,
    ) {}

    public function create(): Response|RedirectResponse
    {
        if (! $this->planAccess->canAddCard(request()->user())) {
            return redirect()
                ->route('pricing')
                ->with('error', __('ui.cards.limit_error'));
        }

        return Inertia::render('Cards/Create');
    }

    public function store(StoreCardRequest $request): RedirectResponse
    {
        abort_unless($this->planAccess->canAddCard($request->user()), 403);

        $request->user()->cards()->create($request->validated());

        return redirect()->route('panel');
    }

    public function show(Request $request, Card $card): Response
    {
        $this->authorize('view', $card);

        $user = $request->user();
        $projection = $this->projection->forCard($card, $user);
        $exchangeRate = $this->exchangeRateResolver->forUser($user);

        return Inertia::render('Cards/Show', [
            'card' => [
                'id' => $card->id,
                'ulid' => $card->ulid,
                'display_name' => $card->displayName(),
                'bank' => $card->bank,
                'last_four' => $card->last_four,
                'type' => $card->type->value,
                'type_label' => $card->type->label(),
                'closing_day' => $card->closing_day,
                'credit_limit' => $card->credit_limit,
                'credit_limit_currency' => $card->credit_limit_currency?->value,
            ],
            'projection' => $projection,
            'showBothCurrencies' => $user->show_both_currencies,
            'preferredCurrency' => $user->preferred_currency->value,
            'exchangeRate' => $exchangeRate['rate'],
            'canImportCsv' => $this->planAccess->canImportStatements($user),
        ]);
    }

    public function edit(Card $card): Response
    {
        $this->authorize('update', $card);

        return Inertia::render('Cards/Edit', [
            'card' => [
                'id' => $card->id,
                'ulid' => $card->ulid,
                'bank' => $card->bank,
                'alias' => $card->alias,
                'last_four' => $card->last_four,
                'type' => $card->type->value,
                'closing_day' => $card->closing_day,
                'credit_limit' => $card->credit_limit,
                'credit_limit_currency' => $card->credit_limit_currency?->value,
            ],
        ]);
    }

    public function update(UpdateCardRequest $request, Card $card): RedirectResponse
    {
        $card->update($request->validated());

        return redirect()->route('cards.show', $card);
    }

    public function destroy(Card $card): RedirectResponse
    {
        $this->authorize('delete', $card);

        $card->delete();

        return redirect()->route('panel');
    }
}
