<?php

namespace App\Models;

use App\Enums\Currency;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Revoltify\Subscriptionify\Concerns\InteractsWithSubscriptions;
use Revoltify\Subscriptionify\Contracts\Subscribable;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Contracts\HasSubscription;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $locale
 * @property string|null $google_id
 * @property bool $is_admin
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property Currency $preferred_currency
 * @property bool $show_both_currencies
 * @property bool $reminders_enabled
 * @property string|null $usd_uyu_rate
 * @property Carbon|null $onboarding_completed_at
 * @property string|null $stripe_id
 * @property string|null $stripe_subscription_id
 * @property string|null $billing_provider
 * @property string|null $mercadopago_subscription_id
 * @property string|null $pm_brand
 * @property string|null $pm_last_four
 * @property int|null $pm_exp_month
 * @property int|null $pm_exp_year
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'locale', 'password', 'google_id', 'is_admin', 'preferred_currency', 'show_both_currencies', 'reminders_enabled', 'usd_uyu_rate', 'onboarding_completed_at', 'stripe_id', 'stripe_subscription_id', 'billing_provider', 'mercadopago_subscription_id', 'pm_brand', 'pm_last_four', 'pm_exp_month', 'pm_exp_year'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference, PasskeyUser, Subscribable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithSubscriptions, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    /**
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * @return HasMany<Installment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<StatementImport, $this>
     */
    public function statementImports(): HasMany
    {
        return $this->hasMany(StatementImport::class);
    }

    /**
     * @return HasMany<PushSubscription, $this>
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    protected function resolveSubscription(): ?HasSubscription
    {
        $subscription = $this->subscriptions()
            ->whereIn('status', [
                SubscriptionStatus::Active,
                SubscriptionStatus::Trialing,
                SubscriptionStatus::PastDue,
                SubscriptionStatus::Cancelled,
            ])
            ->with('plan')
            ->latest()
            ->first();

        if ($subscription === null) {
            return null;
        }

        if ($subscription->getStatus() === SubscriptionStatus::Cancelled && ! $subscription->onGracePeriod()) {
            return null;
        }

        return $subscription;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
            'preferred_currency' => Currency::class,
            'show_both_currencies' => 'boolean',
            'reminders_enabled' => 'boolean',
            'usd_uyu_rate' => 'decimal:4',
            'onboarding_completed_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
