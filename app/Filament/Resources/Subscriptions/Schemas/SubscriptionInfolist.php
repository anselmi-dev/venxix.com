<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Services\AdminMetricsService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Revoltify\Subscriptionify\Models\Subscription;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('subscribable.name')
                    ->label('Usuario'),
                TextEntry::make('subscribable.email')
                    ->label('Correo'),
                TextEntry::make('plan.name')
                    ->label('Plan'),
                TextEntry::make('status')
                    ->label('Estado')
                    ->badge(),
                TextEntry::make('starts_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i'),
                TextEntry::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('trial_ends_at')
                    ->label('Fin de prueba')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('cancelled_at')
                    ->label('Cancelada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('mrr')
                    ->label('MRR estimado')
                    ->state(fn (Subscription $record): string => '$'.number_format(
                        app(AdminMetricsService::class)->subscriptionMrr($record),
                        0,
                        ',',
                        '.',
                    ).' UYU'),
            ]);
    }
}
