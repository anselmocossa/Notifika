<?php

namespace App\Filament\Widgets;

use App\Models\Notification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        return [
            Stat::make('Enviados Hoje', Notification::where('status', 'sent')->where('created_at', '>=', $today)->count())
                ->description('Emails entregues')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Na Fila', Notification::where('status', 'queued')->count())
                ->description('A aguardar envio')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Falhados Hoje', Notification::where('status', 'failed')->where('created_at', '>=', $today)->count())
                ->description('Erros de envio')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Total (30 dias)', Notification::where('created_at', '>=', now()->subDays(30))->count())
                ->description('Últimos 30 dias')
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
