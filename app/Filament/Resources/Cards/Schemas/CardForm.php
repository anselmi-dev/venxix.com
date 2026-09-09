<?php

namespace App\Filament\Resources\Cards\Schemas;

use App\Enums\CardType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('bank')
                    ->required(),
                TextInput::make('alias'),
                TextInput::make('last_four')
                    ->required(),
                Select::make('type')
                    ->options(CardType::class)
                    ->required(),
            ]);
    }
}
