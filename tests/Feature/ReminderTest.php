<?php

use App\Enums\Currency;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\InstallmentDueSoon;
use App\Services\InstallmentGeneratorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

test('pro users receive one reminder for an upcoming installment', function () {
    Carbon::setTestNow('2026-09-06 09:00:00');
    Notification::fake();

    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();
    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Antel',
        'total_amount' => '1200.00',
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => now()->addDays(2)->toDateString(),
    ]);
    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $this->artisan('cuotas:send-reminders')->assertSuccessful();
    $this->artisan('cuotas:send-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $user,
        InstallmentDueSoon::class,
        fn (InstallmentDueSoon $notification, array $channels) => $channels === ['database', 'mail', 'broadcast'],
    );
    Notification::assertSentToTimes($user, InstallmentDueSoon::class, 1);

    expect($purchase->installments()->first()->reminder_sent_at)->not->toBeNull();
});

test('pro users with reminders disabled do not receive installment reminders', function () {
    Carbon::setTestNow('2026-09-06 09:00:00');
    Notification::fake();

    $user = User::factory()->pro()->create(['reminders_enabled' => false]);
    $card = Card::factory()->for($user)->create();
    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'first_due_date' => now()->addDay()->toDateString(),
    ]);
    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $this->artisan('cuotas:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
    expect($purchase->installments()->first()->reminder_sent_at)->toBeNull();
});

test('free users do not receive installment reminders', function () {
    Carbon::setTestNow('2026-09-06 09:00:00');
    Notification::fake();

    $user = User::factory()->create();
    $card = Card::factory()->for($user)->create();
    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'first_due_date' => now()->addDay()->toDateString(),
    ]);
    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $this->artisan('cuotas:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
    expect($purchase->installments()->first()->reminder_sent_at)->toBeNull();
});
