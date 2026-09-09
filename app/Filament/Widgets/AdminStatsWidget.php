<?php

namespace App\Filament\Widgets;

use App\Services\AdminMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Métricas del mes';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $metrics = app(AdminMetricsService::class);

        return [
            Stat::make('Altas del mes', $metrics->signupsThisMonth())
                ->description('Usuarios registrados')
                ->color('success'),
            Stat::make('Cancelaciones', $metrics->cancellationsThisMonth())
                ->description('Suscripciones canceladas este mes')
                ->color('danger'),
            Stat::make('MRR', '$'.number_format($metrics->monthlyRecurringRevenue(), 0, ',', '.').' UYU')
                ->description('Ingreso recurrente mensual estimado')
                ->color('primary'),
        ];
    }
}
