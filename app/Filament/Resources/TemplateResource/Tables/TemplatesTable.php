<?php

namespace App\Filament\Resources\TemplateResource\Tables;

use App\Models\Template;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TemplatesTable
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
            TextColumn::make('display_name')
                ->label('Nome')
                ->searchable()
                ->sortable()
                ->weight('medium')
                ->description(fn (Template $record): ?string => $record->subject),
            TextColumn::make('name')
                ->label('Slug')
                ->searchable()
                ->sortable()
                ->fontFamily('mono')
                ->size('xs'),
            TextColumn::make('channel')
                ->label('Canal')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'email' => 'E-mail',
                    'sms' => 'SMS',
                    'whatsapp' => 'WhatsApp',
                    default => $state,
                })
                ->color(fn (Template $record): string => match ($record->channel) {
                    'email' => 'info',
                    'sms' => 'warning',
                    'whatsapp' => 'success',
                    default => 'gray',
                })
                ->sortable(),
            TextColumn::make('client.name')
                ->label('Cliente')
                ->placeholder('Todos')
                ->badge()
                ->color('gray')
                ->toggleable(),
            IconColumn::make('is_active')
                ->label('Activo')
                ->boolean()
                ->sortable(),
            TextColumn::make('notifications_count')
                ->label('Envios')
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
            SelectFilter::make('channel')
                ->label('Canal')
                ->options([
                    'email' => 'E-mail',
                    'sms' => 'SMS',
                    'whatsapp' => 'WhatsApp',
                ]),
            SelectFilter::make('client_id')
                ->label('Cliente')
                ->relationship('client', 'name'),
        ];
    }
}
