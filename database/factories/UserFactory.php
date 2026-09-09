<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Revoltify\Subscriptionify\Models\Plan;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'onboarding_completed_at' => now(),
            'preferred_currency' => Currency::Uyu,
            'show_both_currencies' => true,
            'reminders_enabled' => true,
            'locale' => 'es',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->assignPlan($user, 'free');
        });
    }

    public function pro(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->assignPlan($user, 'pro');
        });
    }

    public function familiar(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->assignPlan($user, 'familiar');
        });
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'is_admin' => true,
        ]);
    }

    public function onboardingIncomplete(): static
    {
        return $this->state(fn () => [
            'onboarding_completed_at' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    private function assignPlan(User $user, string $slug): void
    {
        $plan = Plan::query()->where('slug', $slug)->first();

        if ($plan === null) {
            return;
        }

        if ($user->subscription() === null) {
            $user->subscribe($plan);

            return;
        }

        if ($user->canChangePlan($plan)) {
            $user->subscription()->changePlan($plan, resetUsages: true);
            $user->clearSubscriptionCache();
        }
    }
}
