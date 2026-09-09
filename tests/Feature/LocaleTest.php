<?php

use App\Enums\Currency;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\InstallmentDueSoon;
use App\Services\InstallmentGeneratorService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;

test('home uses spanish locale by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Landing')
            ->where('locale', 'es')
            ->where('translations.nav.home', 'Inicio')
        );
});

test('first visit respects the accept language header', function () {
    $this->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
        ->get(route('home'))
        ->assertOk()
        ->assertPlainCookie('locale', 'en')
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('translations.nav.home', 'Home')
        );
});

test('locale cookie overrides accept language', function () {
    $this->withUnencryptedCookie('locale', 'es')
        ->withHeaders(['Accept-Language' => 'en'])
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'es'));
});

test('guests can switch locale and persist it in a cookie', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('home'))
        ->assertPlainCookie('locale', 'en');

    $this->withUnencryptedCookie('locale', 'en')
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

test('locale switch rejects unsupported locales', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('locale');
});

test('authenticated users persist locale on their profile', function () {
    $user = User::factory()->create(['locale' => 'es']);

    $this->actingAs($user)
        ->from(route('perfil.show'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('perfil.show'))
        ->assertPlainCookie('locale', 'en');

    expect($user->fresh()->locale)->toBe('en');
});

test('authenticated user locale wins over the cookie', function () {
    $user = User::factory()->create(['locale' => 'es']);

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('panel'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'es'));
});

test('terms page uses english copy when the locale cookie is en', function () {
    $this->withUnencryptedCookie('locale', 'en')
        ->get(route('legal.terms'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Legal/Show')
            ->where('title', 'Terms of service')
            ->where('html', fn (string $html) => str_contains($html, 'id="1-purpose"'))
        );
});

test('installment reminder mail follows the user locale', function () {
    $user = User::factory()->pro()->create(['locale' => 'en']);
    $card = Card::factory()->for($user)->create();
    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Antel',
        'total_amount' => '1200.00',
        'installment_count' => 1,
        'currency' => Currency::Uyu,
        'first_due_date' => now()->addDays(2)->toDateString(),
    ]);
    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $installment = $purchase->installments()->first()->load('purchase.card');

    app()->setLocale($user->preferredLocale());

    $mail = (new InstallmentDueSoon($installment))->toMail($user);
    $payload = (new InstallmentDueSoon($installment))->toArray($user);

    expect($mail->subject)->toBe('An installment for Antel is coming due')
        ->and($payload['title'])->toBe('Installment due soon');
});

test('reset password mail follows the user locale', function () {
    $spanishUser = User::factory()->create(['locale' => 'es']);
    $englishUser = User::factory()->create(['locale' => 'en']);

    app()->setLocale($spanishUser->preferredLocale());
    $spanishMail = (new ResetPassword('test-token'))->toMail($spanishUser);

    app()->setLocale($englishUser->preferredLocale());
    $englishMail = (new ResetPassword('test-token'))->toMail($englishUser);

    expect($spanishMail->subject)->toBe('Restablecé tu contraseña')
        ->and($englishMail->subject)->toBe('Reset your password');
});

test('verify email mail follows the user locale', function () {
    $spanishUser = User::factory()->unverified()->create(['locale' => 'es']);
    $englishUser = User::factory()->unverified()->create(['locale' => 'en']);

    app()->setLocale($spanishUser->preferredLocale());
    $spanishMail = (new VerifyEmail)->toMail($spanishUser);

    app()->setLocale($englishUser->preferredLocale());
    $englishMail = (new VerifyEmail)->toMail($englishUser);

    expect($spanishMail->subject)->toBe('Verificá tu correo electrónico')
        ->and($englishMail->subject)->toBe('Verify your email address');
});
