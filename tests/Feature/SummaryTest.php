<?php

use App\Enums\Currency;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use Illuminate\Support\Carbon;

test('guests are redirected to login from the summary', function () {
    $this->get(route('summary'))
        ->assertRedirect(route('login'));
});

test('summary shows an empty state when the user has no cards', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('summary'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Summary/Index')
            ->has('cards', 0)
            ->where('selectedCardId', null)
            ->where('projection.stats.active_installments', 0)
        );
});

test('summary aggregates pending installments from every card', function () {
    $user = User::factory()->create();
    $firstCard = Card::factory()->for($user)->create(['bank' => 'Santander']);
    $secondCard = Card::factory()->for($user)->create(['bank' => 'Itaú']);

    $firstPurchase = Purchase::factory()->for($user)->for($firstCard)->create([
        'total_amount' => 100,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-10'),
    ]);
    $secondPurchase = Purchase::factory()->for($user)->for($secondCard)->create([
        'total_amount' => 50,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-12'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($firstPurchase);
    (new InstallmentGeneratorService)->createForPurchase($secondPurchase);

    $this->actingAs($user)
        ->get(route('summary'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Summary/Index')
            ->has('cards', 2)
            ->where('selectedCardId', null)
            ->where('projection.stats.pending_uyu', '150.00')
            ->where('projection.stats.active_installments', 2)
        );
});

test('summary can filter installments by card', function () {
    $user = User::factory()->create();
    $firstCard = Card::factory()->for($user)->create();
    $secondCard = Card::factory()->for($user)->create();

    $firstPurchase = Purchase::factory()->for($user)->for($firstCard)->create([
        'total_amount' => 100,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-10'),
    ]);
    $secondPurchase = Purchase::factory()->for($user)->for($secondCard)->create([
        'total_amount' => 50,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-12'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($firstPurchase);
    (new InstallmentGeneratorService)->createForPurchase($secondPurchase);

    $this->actingAs($user)
        ->get(route('summary', ['card' => $firstCard->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selectedCardId', $firstCard->id)
            ->where('projection.stats.pending_uyu', '100.00')
            ->where('projection.stats.active_installments', 1)
        );
});

test('summary returns 404 when the card does not belong to the user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $card = Card::factory()->for($other)->create();

    $this->actingAs($user)
        ->get(route('summary', ['card' => $card->id]))
        ->assertNotFound();
});
