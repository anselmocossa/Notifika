<?php

namespace App\Filament\Widgets;

use App\Models\Notification;
use Filament\Widgets\ChartWidget;

class NotificationsChart extends ChartWidget
{
    protected static ?string $heading = 'Envios (últimos 7 dias)';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());

        $sent = $days->map(fn ($day) =>
            Notification::where('status', 'sent')
                ->whereDate('created_at', $day)
                ->count()
        );

        $failed = $days->map(fn ($day) =>
            Notification::where('status', 'failed')
                ->whereDate('created_at', $day)
                ->count()
        );

        return [
            'datasets' => [
                ['label' => 'Enviados', 'data' => $sent->toArray(), 'backgroundColor' => '#27AE60'],
                ['label' => 'Falhados', 'data' => $failed->toArray(), 'backgroundColor' => '#E74C3C'],
            ],
            'labels' => $days->map(fn ($d) => $d->format('d/m'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
