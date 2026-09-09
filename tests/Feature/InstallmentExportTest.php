<?php

use App\Enums\Currency;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use App\Services\InstallmentGeneratorService;

test('free users cannot export installments', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('installments.export'))
        ->assertForbidden();
});

test('pro users can export pending installments as csv', function () {
    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create(['alias' => 'Visa']);
    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Antel',
        'total_amount' => '1200.00',
        'installment_count' => 2,
        'currency' => Currency::Uyu,
        'first_due_date' => '2026-10-15',
    ]);
    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $response = $this->actingAs($user)
        ->get(route('installments.export', ['card' => $card->id]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)
        ->toContain('comercio,tarjeta,cuota,total_cuotas,monto,moneda,vencimiento,estado')
        ->toContain('Antel')
        ->toContain('Visa');
});
