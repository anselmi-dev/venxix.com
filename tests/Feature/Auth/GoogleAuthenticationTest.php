<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

test('visiting the redirect route sends the user to google', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect();
});

test('a new user is created and logged in on callback', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Ana Pérez',
        'email' => 'ana.perez@example.com',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'ana.perez@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->google_id)->toBe('google-123')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->subscription()?->getPlan()->getSlug())->toBe('free');
});

test('an existing user with the same email is linked instead of duplicated', function () {
    $existing = User::factory()->create([
        'email' => 'carlos@example.com',
        'email_verified_at' => null,
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-456',
        'name' => 'Carlos',
        'email' => 'carlos@example.com',
    ]));

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($existing->fresh());

    expect(User::query()->where('email', 'carlos@example.com')->count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBe('google-456')
        ->and($existing->fresh()->email_verified_at)->not->toBeNull();
});

test('a returning google user is logged into their linked account', function () {
    $existing = User::factory()->create([
        'google_id' => 'google-789',
        'email' => 'returning@example.com',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-789',
        'name' => 'Returning User',
        'email' => 'returning@example.com',
    ]));

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($existing);
    expect(User::query()->count())->toBe(1);
});

test('a denied or failed google authorization redirects back to login as a guest', function () {
    Socialite::shouldReceive('driver')
        ->with('google')
        ->andThrow(new InvalidStateException);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login', absolute: false));
    $this->assertGuest();
});
