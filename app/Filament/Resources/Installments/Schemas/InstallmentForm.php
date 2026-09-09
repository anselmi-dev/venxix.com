<?php

namespace App\Filament\Resources\Installments\Schemas;

use App\Enums\InstallmentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InstallmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('purchase_id')
                    ->relationship('purchase', 'id')
                    ->required(),
                TextInput::make('number')
                    ->required()
                    ->numeric(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                DatePicker::make('due_date')
                    ->required(),
                Select::make('status')
                    ->options(InstallmentStatus::class)
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('paid_at'),
                DateTimePicker::make('reminder_sent_at'),
            ]);
    }
}
