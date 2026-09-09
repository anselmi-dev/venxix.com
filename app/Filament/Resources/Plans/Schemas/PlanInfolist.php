<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Revoltify\Subscriptionify\Models\Plan;

class PlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('slug')
                    ->label('Slug'),
                TextEntry::make('description')
                    ->label('Descripción')
                    ->placeholder('—'),
                IconEntry::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                IconEntry::make('is_free')
                    ->label('Gratis')
                    ->boolean(),
                TextEntry::make('billing_interval')
                    ->label('Intervalo')
                    ->badge(),
                TextEntry::make('trial_days')
                    ->label('Días de prueba'),
                TextEntry::make('grace_days')
                    ->label('Días de gracia'),
                TextEntry::make('cards_limit')
                    ->label('Límite de tarjetas')
                    ->state(function (Plan $record): string {
                        $feature = $record->features()->where('slug', 'cards')->first();
                        $value = $feature?->pivot?->value;

                        return $value === '0' ? 'Ilimitado' : (string) $value;
                    }),
            ]);
    }
}
