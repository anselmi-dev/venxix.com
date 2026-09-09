<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\ImpersonationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('billing_provider')
                    ->label('Proveedor')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('impersonate')
                    ->label('Impersonar')
                    ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
                    ->requiresConfirmation()
                    ->modalHeading('Impersonar usuario')
                    ->modalDescription('Vas a iniciar sesión como este usuario para dar soporte.')
                    ->visible(fn (User $record): bool => ! $record->is_admin)
                    ->action(function (User $record, Action $action): void {
                        app(ImpersonationService::class)->start($record);
                        $action->redirect(route('panel'));
                    }),
            ]);
    }
}
