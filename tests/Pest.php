<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Gateway credential isolation
|--------------------------------------------------------------------------
|
| Real gateway credentials in the developer's own .env (e.g. after running
| `dlocalgo:sync-plans`) would otherwise leak into the test environment,
| and a credential a test sets via config() isn't reset between tests the
| way the database is either — either way a test could see a gateway as
| "configured" when it didn't ask for that. Force a clean slate before
| every test; tests that need a gateway "configured" set it explicitly.
|
*/

beforeEach(function (): void {
    config([
        'services.stripe.secret' => null,
        'services.stripe.webhook_secret' => null,
        'services.mercadopago.access_token' => null,
        'services.mercadopago.webhook_secret' => null,
        'services.dlocalgo.api_key' => null,
        'services.dlocalgo.secret_key' => null,
    ]);
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
