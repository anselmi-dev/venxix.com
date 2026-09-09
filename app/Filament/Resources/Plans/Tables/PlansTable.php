<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Revoltify\Subscriptionify\Models\Plan;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                IconColumn::make('is_free')
                    ->label('Gratis')
                    ->boolean(),
                TextColumn::make('billing_interval')
                    ->label('Intervalo')
                    ->badge(),
                TextColumn::make('trial_days')
                    ->label('Prueba (días)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('grace_days')
                    ->label('Gracia (días)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Precio config')
                    ->state(function (Plan $record): string {
                        if ($record->is_free) {
                            return 'Gratis';
                        }

                        $family = match ($record->slug) {
                            'pro', 'pro-yearly' => 'pro',
                            'familiar', 'familiar-yearly' => 'familiar',
                            default => null,
                        };

                        if ($family === null) {
                            return '—';
                        }

                        $prices = config("cuotas.prices.{$family}");
                        $amount = $record->billing_interval->value === 'year'
                            ? $prices['yearly']
                            : $prices['monthly'];

                        return '$'.number_format($amount, 0, ',', '.').' UYU';
                    }),
                TextColumn::make('active_subscriptions')
                    ->label('Suscripciones activas')
                    ->counts('subscriptions')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
