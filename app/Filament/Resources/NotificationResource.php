<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Filament\Resources\NotificationResource\Pages\ViewNotification;
use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell';
    protected static string | \UnitEnum | null $navigationGroup = 'Notificações';
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

                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('created_at')
                    ->label('Criado')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
            TextEntry::make('to')->label('Destinatário'),
            TextEntry::make('subject')->label('Assunto'),
            TextEntry::make('content')->label('Conteúdo')->columnSpanFull(),
            TextEntry::make('status')->label('Estado')->badge(),
            TextEntry::make('attempts')->label('Tentativas'),
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
