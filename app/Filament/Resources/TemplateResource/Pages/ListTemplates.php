<?php

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource;
use App\Models\Template;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo modelo'),
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
                ->badge(fn () => Template::query()->where('is_active', true)->count()),
            'inactivos' => Tab::make('Inactivos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => Template::query()->where('is_active', false)->count())
                ->badgeColor('gray'),
            'todos' => Tab::make('Todos')
                ->badge(fn () => Template::query()->count()),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'activos';
    }
}
