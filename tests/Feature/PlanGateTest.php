<?php

use App\Enums\CardType;
use App\Models\Card;
use App\Models\User;

test('a free user is forbidden from creating a second card', function () {
    $user = User::factory()->create();
    Card::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('cards.store'), [
            'bank' => 'Itaú',
            'alias' => 'Extra',
            'last_four' => '4242',
            'type' => CardType::Credit->value,
        ])
        ->assertForbidden();
});

test('a pro user can create a second card', function () {
    $user = User::factory()->pro()->create();
    Card::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('cards.store'), [
            'bank' => 'Itaú',
            'alias' => 'Extra',
            'last_four' => '4242',
            'type' => CardType::Credit->value,
        ])
        ->assertRedirect(route('panel'));

    expect($user->cards()->count())->toBe(2);
});

test('creating a second card on the free plan redirects to pricing', function () {
    $user = User::factory()->create();
    Card::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('cards.create'))
        ->assertRedirect(route('pricing'));
});

test('a free user is forbidden from creating notes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'body' => 'Recordar pagar Ancap',
        ])
        ->assertForbidden();
});

test('a pro user can create notes', function () {
    $user = User::factory()->pro()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'body' => 'Recordar pagar Ancap',
        ])
        ->assertRedirect();

    expect($user->notes()->count())->toBe(1);
});
