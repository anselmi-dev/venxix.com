<?php

use App\Enums\Currency;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use App\Services\InstallmentProjectionService;
use Illuminate\Support\Carbon;

test('projection groups installments by month for card', function () {
    $user = User::factory()->create();
    $card = Card::factory()->for($user)->create();

    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Fotostanisic',
        'total_amount' => 600,
        'installment_count' => 3,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-10'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $projection = app(InstallmentProjectionService::class)->forCard($card, $user);

    expect($projection['months'])->toHaveCount(3);
    expect($projection['stats']['active_installments'])->toBe(3);
    expect($projection['chart'])->not->toBeEmpty();
});

test('projection chart breaks each month down into installment segments sorted by amount', function () {
    $user = User::factory()->create(['preferred_currency' => Currency::Uyu]);
    $card = Card::factory()->for($user)->create();

    $small = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Kiosco',
        'total_amount' => 300,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-10'),
    ]);
    $big = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Fotostanisic',
        'total_amount' => 900,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-15'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($small);
    (new InstallmentGeneratorService)->createForPurchase($big);

    $chart = app(InstallmentProjectionService::class)->forCard($card, $user)['chart'];

    expect($chart)->toHaveCount(1);
    expect($chart[0]['amount'])->toBe(1200.0);
    expect($chart[0]['segments'])->toHaveCount(2);
    expect(array_column($chart[0]['segments'], 'merchant'))->toBe(['Fotostanisic', 'Kiosco']);
    expect(array_column($chart[0]['segments'], 'amount'))->toBe([900.0, 300.0]);
    expect(array_sum(array_column($chart[0]['segments'], 'amount')))->toBe($chart[0]['amount']);
});

test('projection can aggregate pending installments for the whole user', function () {
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
        'total_amount' => 40,
        'installment_count' => 1,
        'currency' => Currency::Usd,
        'first_due_date' => Carbon::parse('2026-10-10'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($firstPurchase);
    (new InstallmentGeneratorService)->createForPurchase($secondPurchase);

    $projection = app(InstallmentProjectionService::class)->forUser($user);

    expect($projection['stats']['pending_uyu'])->toBe('100.00');
    expect($projection['stats']['pending_usd'])->toBe('40.00');
    expect($projection['stats']['active_installments'])->toBe(2);
    expect($projection['months'][0]['items'][0]['card_label'])->not->toBeNull();
});
