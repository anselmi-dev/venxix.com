<?php

use App\Enums\Currency;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\InstallmentDueSoon;
use App\Services\InstallmentGeneratorService;
use Illuminate\Support\Facades\Notification;

test('installment reminders are broadcastable', function () {
    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();
    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'currency' => Currency::Uyu,
        'first_due_date' => now()->addDay()->toDateString(),
    ]);
    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $installment = $purchase->installments()->firstOrFail();
    $notification = new InstallmentDueSoon($installment);

    expect($notification->via($user))->toBe(['database', 'mail', 'broadcast']);
    expect($notification->toBroadcast($user)->data)
        ->toHaveKey('title')
        ->toHaveKey('description');
});

test('sending an installment reminder dispatches a broadcast notification', function () {
    Notification::fake();

    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();
    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'first_due_date' => now()->addDay()->toDateString(),
    ]);
    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $user->notify(new InstallmentDueSoon($purchase->installments()->firstOrFail()));

    Notification::assertSentTo(
        $user,
        InstallmentDueSoon::class,
        fn (InstallmentDueSoon $notification, array $channels) => in_array('broadcast', $channels, true),
    );
});
