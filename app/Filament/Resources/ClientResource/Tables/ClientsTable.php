<?php

namespace App\Filament\Resources\ClientResource\Tables;

use App\Models\Client;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns(self::colunas())
            ->filters(self::filtros())
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function colunas(): array
    {
        return [
            TextColumn::make('name')
                ->label('Nome')
                ->searchable()
                ->sortable()
                ->weight('medium')
                ->description(fn (Client $record): ?string => $record->brand_name),
            TextColumn::make('slug')
                ->label('Slug')
                ->searchable()
                ->sortable()
                ->fontFamily('mono')
                ->size('xs'),
            IconColumn::make('is_active')
                ->label('Activo')
                ->boolean()
                ->sortable(),
            TextColumn::make('monthly_budget')
                ->label('Orçamento mensal')
                ->placeholder('—')
                ->formatStateUsing(fn (?string $state, Client $record): string => $state === null
                    ? '—'
                    : number_format((float) $state, 2, ',', '.').' '.($record->budget_currency ?: 'MZN'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('notifications_count')
                ->label('Notificações')
                ->counts('notifications')
                ->badge()
                ->color('info')
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Criado em')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function filtros(): array
    {
        return [
            TernaryFilter::make('is_active')->label('Activo'),
        ];
    }
}
