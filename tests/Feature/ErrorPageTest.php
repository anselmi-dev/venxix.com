<?php

use App\Models\Card;
use App\Models\User;

test('unknown routes render the inertia 404 page', function () {
    $this->get('/esta-ruta-no-existe')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('Errors/Show')
            ->where('status', 404)
        );
});

test('forbidden card access renders the inertia 403 page', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $card = Card::factory()->for($owner)->create();

    $this->actingAs($other)
        ->get(route('cards.show', $card))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('Errors/Show')
            ->where('status', 403)
        );
});
