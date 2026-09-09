<?php

use App\Models\User;

test('name can be updated from the perfil page', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $response = $this
        ->actingAs($user)
        ->from(route('perfil.show'))
        ->patch(route('perfil.update'), [
            'name' => 'New Name',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('perfil.show'));

    expect($user->refresh()->name)->toBe('New Name');
});

test('email cannot be changed from the perfil page', function () {
    $user = User::factory()->create(['email' => 'original@example.com']);

    $this
        ->actingAs($user)
        ->from(route('perfil.show'))
        ->patch(route('perfil.update'), [
            'name' => $user->name,
            'email' => 'changed@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('perfil.show'));

    expect($user->refresh()->email)->toBe('original@example.com');
});
