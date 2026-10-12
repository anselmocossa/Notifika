<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->badge(fn () => Notification::query()->count()),
            'na_fila' => Tab::make('Na fila')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'queued'))
                ->badge(fn () => Notification::query()->where('status', 'queued')->count())
                ->badgeColor('gray'),
            'enviadas' => Tab::make('Enviadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent'))
                ->badge(fn () => Notification::query()->where('status', 'sent')->count())
                ->badgeColor('success'),
            'falhadas' => Tab::make('Falhadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => Notification::query()->where('status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'todas';
    }
}
