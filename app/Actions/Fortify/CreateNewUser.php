<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Revoltify\Subscriptionify\Models\Plan;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Fortify's register route has no configurable rate-limiter key (unlike
        // login/two-factor/passkeys), so registration is throttled here instead.
        $throttleKey = 'register:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.register_throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ])->status(429);
        }

        RateLimiter::hit($throttleKey, 60);

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $free = Plan::query()->where('slug', 'free')->first();

        if ($free !== null) {
            $user->subscribe($free);
        }

        return $user;
    }
}
