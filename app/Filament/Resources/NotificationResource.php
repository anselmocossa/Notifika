<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Filament\Resources\NotificationResource\Pages\ViewNotification;
use App\Models\Notification;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static string|\UnitEnum|null $navigationGroup = 'Notificações';

    protected static ?string $label = 'Notificação';

    protected static ?string $pluralLabel = 'Notificações';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('external_id')
                    ->label('ID')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->badge()
                    ->searchable(),

                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'email' => 'info',
                        'sms' => 'warning',
                        'whatsapp' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('to')
                    ->label('Destinatário')
                    ->searchable(),

                TextColumn::make('subject')
                    ->label('Assunto')
                    ->limit(30),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'queued' => 'gray',
                        'processing' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('attempts')
                    ->label('Tentativas'),

                TextColumn::make('segments')
                    ->label('Segmentos')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('cost')
                    ->label('Custo')
                    ->toggleable()
                    ->formatStateUsing(fn ($state, $record) => $state !== null
                        ? number_format((float) $state, 4).' '.($record->cost_currency ?? '')
                        : '—'),

                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('created_at')
                    ->label('Criado')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
                    ->options([
                        'queued' => 'Na fila',
                        'processing' => 'Processando',
                        'sent' => 'Enviado',
                        'failed' => 'Falhado',
                    ]),
            ])
            ->poll('10s');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('external_id')->label('ID'),
            TextEntry::make('client.name')->label('Cliente'),
            TextEntry::make('channel')->label('Canal')->badge(),
            TextEntry::make('driver')->label('Driver')->placeholder('—'),
            TextEntry::make('to')->label('Destinatário'),
            TextEntry::make('subject')->label('Assunto')->visible(fn ($record) => $record->channel === 'email'),
            TextEntry::make('content')->label('Conteúdo')->columnSpanFull(),
            TextEntry::make('status')->label('Estado')->badge(),
            TextEntry::make('attempts')->label('Tentativas'),
            TextEntry::make('encoding')->label('Codificação')->placeholder('—'),
            TextEntry::make('segments')->label('Segmentos')->placeholder('—'),
            TextEntry::make('provider_message_id')->label('ID do fornecedor')->placeholder('—'),
            TextEntry::make('cost')
                ->label('Custo')
                ->placeholder('—')
                ->formatStateUsing(fn ($state, $record) => $state !== null
                    ? number_format((float) $state, 4).' '.($record->cost_currency ?? '')
                    : '—'),
            TextEntry::make('error_message')
                ->label('Erro')
                ->visible(fn ($record) => $record->status === 'failed'),
            TextEntry::make('sent_at')->label('Enviado em')->dateTime('d/m/Y H:i:s'),
            TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i:s'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotifications::route('/'),
            'view' => ViewNotification::route('/{record}'),
        ];
    }
}
