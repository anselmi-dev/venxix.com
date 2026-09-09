<?php

use App\Enums\Currency;
use App\Enums\InstallmentStatus;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Card;
use App\Models\Note;
use App\Models\Purchase;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use Illuminate\Support\Carbon;

test('panel shows pending stats for user', function () {
    $user = User::factory()->create();
    $card = Card::factory()->for($user)->create();

    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'total_amount' => 300,
        'installment_count' => 2,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-10'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $this->actingAs($user)
        ->get(route('panel'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Panel/Index')
            ->where('stats.pending_uyu', '300.00')
            ->has('cards', 1)
            ->has('cards.0.sparkline', 6)
            ->where('cards.0.sparkline.0.amount_uyu', 150)
            ->where('cards.0.sparkline.0.count', 1)
            ->where('cards.0.sparkline.0.label', 'Sep 2026')
        );
});

test('panel calendar is empty on the free plan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('panel', ['year' => 2026, 'month' => 9]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('features.calendar', false)
            ->where('features.notes', false)
            ->where('calendar.markedDays', [])
            ->has('calendar.events', 0)
        );
});

test('panel calendar lists installments for selected month', function () {
    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();

    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Decathlon',
        'total_amount' => 100,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-15'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $this->actingAs($user)
        ->get(route('panel', ['year' => 2026, 'month' => 9]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('calendar.markedDays', [15])
            ->has('calendar.events', 1)
        );
});

test('panel calendar only marks days that match the status filter', function () {
    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();

    $paidPurchase = Purchase::factory()->for($user)->for($card)->create([
        'total_amount' => 100,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-05'),
    ]);
    $pendingPurchase = Purchase::factory()->for($user)->for($card)->create([
        'total_amount' => 200,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-20'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($paidPurchase);
    (new InstallmentGeneratorService)->createForPurchase($pendingPurchase);

    $paidPurchase->installments()->first()->update(['status' => InstallmentStatus::Paid]);

    $this->actingAs($user)
        ->get(route('panel', ['year' => 2026, 'month' => 9, 'status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('calendar.markedDays', [5])
            ->has('calendar.events', 1)
        );
});

test('panel calendar lists the card closing day separately from installment due dates', function () {
    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->withStatement(closingDay: 20)->create();

    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Decathlon',
        'total_amount' => 100,
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => Carbon::parse('2026-09-15'),
    ]);

    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $this->actingAs($user)
        ->get(route('panel', ['year' => 2026, 'month' => 9]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('calendar.markedDays', [15])
            ->where('calendar.closingDays', [20])
            ->has('calendar.events', 2)
            ->where('calendar.events.0.type', 'due')
            ->where('calendar.events.0.day', 15)
            ->where('calendar.events.1.type', 'closing')
            ->where('calendar.events.1.day', 20)
        );
});

test('a card closing day past the end of a shorter month is clamped to the last day', function () {
    $user = User::factory()->pro()->create();
    Card::factory()->for($user)->withStatement(closingDay: 31)->create();

    $this->actingAs($user)
        ->get(route('panel', ['year' => 2026, 'month' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('calendar.closingDays', [28])
        );
});

test('panel exposes note archive state and the character limit', function () {
    $user = User::factory()->pro()->create();
    Note::factory()->for($user)->create();
    Note::factory()->for($user)->archived()->create();
    $assetVersion = (string) app(HandleInertiaRequests::class)->version(request());

    $response = $this->actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $assetVersion,
        ])
        ->get(route('panel'))
        ->assertOk();

    expect($response->json('props.noteMaxLength'))->toBe(Note::BODY_MAX_LENGTH);
    expect($response->json('props.notes'))->toHaveCount(2);
    expect(collect($response->json('props.notes'))->pluck('archived_at')->filter()->count())->toBe(1);
});

test('panel calendar supports partial updates without reloading unrelated data', function () {
    $user = User::factory()->pro()->create();
    $assetVersion = (string) app(HandleInertiaRequests::class)->version(request());

    $this->actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $assetVersion,
            'X-Inertia-Partial-Component' => 'Panel/Index',
            'X-Inertia-Partial-Data' => 'calendar',
        ])
        ->get(route('panel', [
            'year' => 2026,
            'month' => 10,
            'status' => 'pending',
        ]))
        ->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Panel/Index')
        ->assertJsonPath('props.calendar.year', 2026)
        ->assertJsonPath('props.calendar.month', 10)
        ->assertJsonPath('props.calendar.statusFilter', 'pending')
        ->assertJsonMissingPath('props.stats')
        ->assertJsonMissingPath('props.cards')
        ->assertJsonMissingPath('props.notes');
});
