<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Models\User;
use App\Services\AdminMetricsService;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Revoltify\Subscriptionify\Models\Subscription;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscribable.name')
                    ->label('Usuario')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHasMorph('subscribable', [User::class], function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('cancelled_at')
                    ->label('Cancelada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('mrr')
                    ->label('MRR')
                    ->state(fn (Subscription $record): string => '$'.number_format(
                        app(AdminMetricsService::class)->subscriptionMrr($record),
                        0,
                        ',',
                        '.',
                    ).' UYU'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
