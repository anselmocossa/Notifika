<?php

namespace App\Filament\Resources\NotificationResource\Tables;

use App\Models\Notification;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->poll('10s')
            ->columns(self::colunas())
            ->filters(self::filtros())
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function colunas(): array
    {
        return [
            TextColumn::make('external_id')
                ->label('ID')
                ->searchable()
                ->sortable()
                ->fontFamily('mono')
                ->size('xs')
                ->copyable(),
            TextColumn::make('client.name')
                ->label('Cliente')
                ->badge()
                ->placeholder('—')
                ->searchable()
                ->sortable(),
            TextColumn::make('channel')
                ->label('Canal')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'email' => 'E-mail',
                    'sms' => 'SMS',
                    'whatsapp' => 'WhatsApp',
                    default => $state ?: '—',
                })
                ->color(fn (Notification $record): string => match ($record->channel) {
                    'email' => 'info',
                    'sms' => 'warning',
                    'whatsapp' => 'success',
                    default => 'gray',
                })
                ->sortable(),
            TextColumn::make('to')
                ->label('Destinatário')
                ->searchable()
                ->sortable()
                ->weight('medium'),
            TextColumn::make('subject')
                ->label('Assunto')
                ->limit(40)
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('template.name')
                ->label('Modelo')
                ->badge()
                ->color('gray')
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('status')
                ->label('Estado')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'queued' => 'Na fila',
                    'processing' => 'A processar',
                    'sent' => 'Enviado',
                    'failed' => 'Falhado',
                    default => $state ?: '—',
                })
                ->color(fn (Notification $record): string => match ($record->status) {
                    'queued' => 'gray',
                    'processing' => 'warning',
                    'sent' => 'success',
                    'failed' => 'danger',
                    default => 'gray',
                })
                ->sortable(),
            TextColumn::make('attempts')
                ->label('Tentativas')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('segments')
                ->label('Segmentos')
                ->placeholder('—')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('cost')
                ->label('Custo')
                ->placeholder('—')
                ->toggleable(isToggledHiddenByDefault: true)
                ->formatStateUsing(fn ($state, Notification $record): string => $state !== null
                    ? number_format((float) $state, 4, ',', '.').' '.($record->cost_currency ?? '')
                    : '—'),
            TextColumn::make('sent_at')
                ->label('Enviado em')
                ->dateTime('d/m/Y H:i')
                ->placeholder('—')
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
            SelectFilter::make('client_id')
                ->label('Cliente')
                ->relationship('client', 'name'),
            SelectFilter::make('channel')
                ->label('Canal')
                ->options([
                    'email' => 'E-mail',
                    'sms' => 'SMS',
                    'whatsapp' => 'WhatsApp',
                ]),
            SelectFilter::make('status')
                ->label('Estado')
                ->options([
                    'queued' => 'Na fila',
                    'processing' => 'A processar',
                    'sent' => 'Enviado',
                    'failed' => 'Falhado',
                ]),
        ];
    }
}
