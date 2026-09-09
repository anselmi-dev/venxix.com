<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('two_factor_secret')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('two_factor_recovery_codes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('two_factor_confirmed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('preferred_currency')
                    ->badge(),
                IconEntry::make('show_both_currencies')
                    ->boolean(),
                TextEntry::make('usd_uyu_rate')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('onboarding_completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('stripe_id')
                    ->placeholder('-'),
                TextEntry::make('stripe_subscription_id')
                    ->placeholder('-'),
                TextEntry::make('pm_brand')
                    ->placeholder('-'),
                TextEntry::make('pm_last_four')
                    ->placeholder('-'),
                TextEntry::make('pm_exp_month')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('pm_exp_year')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('billing_provider')
                    ->placeholder('-'),
                TextEntry::make('mercadopago_subscription_id')
                    ->placeholder('-'),
                IconEntry::make('reminders_enabled')
                    ->boolean(),
            ]);
    }
}
