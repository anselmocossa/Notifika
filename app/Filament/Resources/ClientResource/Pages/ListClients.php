<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo cliente'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'activos' => Tab::make('Activos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => Client::query()->where('is_active', true)->count()),
            'inactivos' => Tab::make('Inactivos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => Client::query()->where('is_active', false)->count())
                ->badgeColor('gray'),
            'todos' => Tab::make('Todos')
                ->badge(fn () => Client::query()->count()),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'activos';
    }
}
