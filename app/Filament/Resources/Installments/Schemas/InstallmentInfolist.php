<?php

namespace App\Filament\Resources\Installments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InstallmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('purchase.id')
                    ->label('Purchase'),
                TextEntry::make('number')
                    ->numeric(),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('due_date')
                    ->date(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('reminder_sent_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
