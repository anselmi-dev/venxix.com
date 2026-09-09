<?php

namespace App\Filament\Resources\Installments;

use App\Filament\Resources\Installments\Pages\ListInstallments;
use App\Filament\Resources\Installments\Pages\ViewInstallment;
use App\Filament\Resources\Installments\Schemas\InstallmentInfolist;
use App\Filament\Resources\Installments\Tables\InstallmentsTable;
use App\Models\Installment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InstallmentResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'cuota';

    protected static ?string $pluralModelLabel = 'cuotas';

    protected static ?string $navigationLabel = 'Cuotas';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return InstallmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstallmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstallments::route('/'),
            'view' => ViewInstallment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
