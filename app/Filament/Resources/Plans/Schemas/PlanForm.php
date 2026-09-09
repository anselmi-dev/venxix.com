<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Revoltify\Subscriptionify\Models\Plan;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),
                TextInput::make('trial_days')
                    ->label('Días de prueba')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('grace_days')
                    ->label('Días de gracia')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->required(),
                TextInput::make('cards_limit')
                    ->label('Límite de tarjetas')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('0 = ilimitado en planes de pago.'),
                Placeholder::make('config_prices')
                    ->label('Precios (config/cuotas.php)')
                    ->content(function (?Plan $record): string {
                        if ($record === null || $record->is_free) {
                            return 'Los precios del plan gratis están en cero.';
                        }

                        $family = match ($record->slug) {
                            'pro', 'pro-yearly' => 'pro',
                            'familiar', 'familiar-yearly' => 'familiar',
                            default => null,
                        };

                        if ($family === null) {
                            return 'Sin familia de precios configurada.';
                        }

                        $prices = config("cuotas.prices.{$family}");

                        return sprintf(
                            'Mensual: $%s UYU — Anual: $%s UYU',
                            number_format($prices['monthly'], 0, ',', '.'),
                            number_format($prices['yearly'], 0, ',', '.'),
                        );
                    }),
            ]);
    }
}
