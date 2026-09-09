<?php

use App\Enums\CardType;
use App\Enums\Currency;
use App\Models\Card;
use App\Models\User;

test('user can create a card with last four digits only', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cards.store'), [
            'bank' => 'Santander',
            'alias' => 'Principal',
            'last_four' => '1796',
            'type' => CardType::Credit->value,
        ])
        ->assertRedirect(route('panel'));

    $card = Card::query()->first();

    expect($card)->not->toBeNull();
    expect($card->last_four)->toBe('1796');
    expect($card->user_id)->toBe($user->id);
});

test('user cannot view another users card', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $card = Card::factory()->for($owner)->create();

    $this->actingAs($other)
        ->get(route('cards.show', $card))
        ->assertForbidden();
});

test('card routes are keyed by ulid instead of the numeric id', function () {
    $user = User::factory()->create();
    $card = Card::factory()->for($user)->create();

    expect($card->getRouteKey())->toBe($card->ulid)
        ->and($card->ulid)->not->toBe((string) $card->id)
        ->and(route('cards.show', $card))->toContain($card->ulid)
        ->and(route('cards.show', $card))->not->toContain('/tarjetas/'.$card->id);

    $this->actingAs($user)
        ->get('/tarjetas/'.$card->id)
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('cards.show', $card))
        ->assertOk();
});

test('user can create a card with a closing day and credit limit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cards.store'), [
            'bank' => 'Santander',
            'alias' => 'Principal',
            'last_four' => '1796',
            'type' => CardType::Credit->value,
            'closing_day' => 15,
            'credit_limit' => 50000,
            'credit_limit_currency' => 'UYU',
        ])
        ->assertRedirect(route('panel'));

    $card = Card::query()->first();

    expect($card->closing_day)->toBe(15);
    expect($card->credit_limit)->toBe('50000.00');
    expect($card->credit_limit_currency)->toBe(Currency::Uyu);
});

test('closing day must be a valid day of the month', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cards.store'), [
            'bank' => 'Santander',
            'last_four' => '1796',
            'type' => CardType::Credit->value,
            'closing_day' => 32,
        ])
        ->assertInvalid(['closing_day']);
});

test('credit limit requires a currency', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cards.store'), [
            'bank' => 'Santander',
            'last_four' => '1796',
            'type' => CardType::Credit->value,
            'credit_limit' => 50000,
        ])
        ->assertInvalid(['credit_limit_currency']);
});
