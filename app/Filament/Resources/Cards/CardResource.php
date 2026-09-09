<?php

namespace App\Filament\Resources\Cards;

use App\Filament\Resources\Cards\Pages\ListCards;
use App\Filament\Resources\Cards\Pages\ViewCard;
use App\Filament\Resources\Cards\Schemas\CardInfolist;
use App\Filament\Resources\Cards\Tables\CardsTable;
use App\Models\Card;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'tarjeta';

    protected static ?string $pluralModelLabel = 'tarjetas';

    protected static ?string $navigationLabel = 'Tarjetas';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return CardInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCards::route('/'),
            'view' => ViewCard::route('/{record}'),
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
