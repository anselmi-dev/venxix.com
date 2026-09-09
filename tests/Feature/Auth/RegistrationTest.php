<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('panel', absolute: false));
});

test('registration is rate limited', function () {
    RateLimiter::increment('register:127.0.0.1', amount: 5);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->assertDatabaseMissing(User::class, ['email' => 'test@example.com']);
});
