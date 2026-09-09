<?php

namespace App\Billing;

use App\Models\User;

class PlanAccess
{
    public const Cards = 'cards';

    public const Calendar = 'calendar';

    public const Notes = 'notes';

    public const Reminders = 'reminders';

    public const Export = 'export';

    public const FxAuto = 'fx-auto';

    public const ImportCsv = 'import-csv';

    public function family(User $user): string
    {
        $slug = $user->subscription()?->getPlan()->getSlug() ?? 'free';

        return match ($slug) {
            'pro', 'pro-yearly' => 'pro',
            'familiar', 'familiar-yearly' => 'familiar',
            default => 'free',
        };
    }

    public function canAddCard(User $user): bool
    {
        if (! $user->subscribed() || ! $user->hasFeature(self::Cards)) {
            return $user->cards()->count() < 1;
        }

        if ($user->isUnlimitedUsage(self::Cards)) {
            return true;
        }

        return $user->cards()->count() < (int) $user->featureInfo(self::Cards)->limit;
    }

    public function hasCalendar(User $user): bool
    {
        return $user->subscribed() && $user->hasFeature(self::Calendar);
    }

    public function hasNotes(User $user): bool
    {
        return $user->subscribed() && $user->hasFeature(self::Notes);
    }

    public function hasReminders(User $user): bool
    {
        return $user->subscribed() && $user->hasFeature(self::Reminders);
    }

    public function hasAutomaticExchangeRate(User $user): bool
    {
        return $user->subscribed() && $user->hasFeature(self::FxAuto);
    }

    public function canImportStatements(User $user): bool
    {
        return $user->subscribed() && $user->hasFeature(self::ImportCsv);
    }

    public function canExport(User $user): bool
    {
        return $user->subscribed() && $user->hasFeature(self::Export);
    }

    public function wantsReminders(User $user): bool
    {
        return $this->hasReminders($user) && $user->reminders_enabled;
    }

    /**
     * @return array{
     *     calendar: bool,
     *     notes: bool,
     *     reminders: bool,
     *     export: bool,
     *     fx_auto: bool,
     *     import_csv: bool,
     *     can_add_card: bool,
     *     family: string
     * }
     */
    public function features(User $user): array
    {
        return [
            'calendar' => $this->hasCalendar($user),
            'notes' => $this->hasNotes($user),
            'reminders' => $this->hasReminders($user),
            'export' => $this->canExport($user),
            'fx_auto' => $this->hasAutomaticExchangeRate($user),
            'import_csv' => $this->canImportStatements($user),
            'can_add_card' => $this->canAddCard($user),
            'family' => $this->family($user),
        ];
    }
}
