<?php

namespace App\Filament\Resources\Installments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstallmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase.description')
                    ->label('Compra')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('number')
                    ->label('Nº')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('UYU')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('paid_at')
                    ->label('Pagada')
                    ->dateTime('d/m/Y')
                    ->placeholder('—'),
            ])
            ->defaultSort('due_date', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
