<?php

use App\Enums\Currency;
use App\Enums\InstallmentStatus;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use Illuminate\Support\Carbon;

test('purchase creates installments that sum to total', function () {
    $user = User::factory()->create();
    $card = Card::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('panel'))
        ->post(route('purchases.store'), [
            'card_id' => $card->id,
            'merchant' => 'Fotostanisic',
            'total_amount' => 1000,
            'installment_count' => 3,
            'currency' => Currency::Uyu->value,
            'first_due_date' => '2026-09-10',
        ])
        ->assertRedirect(route('panel'));

    $purchase = Purchase::query()->first();
    $installments = $purchase->installments;

    expect($installments)->toHaveCount(3);
    expect((float) $installments->sum('amount'))->toBe(1000.0);
    expect($installments->first()->due_date->toDateString())->toBe('2026-09-10');
    expect($installments->last()->due_date->toDateString())->toBe('2026-11-10');
});

test('adding a purchase from a card page redirects back to that card', function () {
    $user = User::factory()->create();
    $card = Card::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('cards.show', $card))
        ->post(route('purchases.store'), [
            'card_id' => $card->id,
            'merchant' => 'Fotostanisic',
            'total_amount' => 500,
            'installment_count' => 1,
            'currency' => Currency::Uyu->value,
            'first_due_date' => '2026-09-10',
        ])
        ->assertRedirect(route('cards.show', $card));
});

test('user can mark installment as paid', function () {
    $user = User::factory()->create();
    $purchase = Purchase::factory()->for($user)->create([
        'installment_count' => 2,
        'total_amount' => 200,
        'first_due_date' => Carbon::parse('2026-09-01'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $installment = $purchase->installments()->first();

    $this->actingAs($user)
        ->patch(route('installments.update', $installment), ['status' => InstallmentStatus::Paid->value])
        ->assertRedirect();

    expect($installment->fresh()->status)->toBe(InstallmentStatus::Paid);
    expect($installment->fresh()->paid_at)->not->toBeNull();
});
