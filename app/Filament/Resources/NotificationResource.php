<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Notificações';
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
                Tables\Columns\TextColumn::make('external_id')
                    ->label('ID')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('to')
                    ->label('Destinatário')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Assunto')
                    ->limit(30),

                Tables\Columns\TextColumn::make('template.name')
                    ->label('Template')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'queued' => 'gray',
                        'processing' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('attempts')
                    ->label('Tentativas'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'queued' => 'Na fila',
                        'processing' => 'Processando',
                        'sent' => 'Enviado',
                        'failed' => 'Falhado',
                    ]),
            ])
            ->poll('10s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('external_id')->label('ID'),
            Infolists\Components\TextEntry::make('to')->label('Destinatário'),
            Infolists\Components\TextEntry::make('subject')->label('Assunto'),
            Infolists\Components\TextEntry::make('content')->label('Conteúdo')->columnSpanFull(),
            Infolists\Components\TextEntry::make('status')->label('Estado')->badge(),
            Infolists\Components\TextEntry::make('attempts')->label('Tentativas'),
            Infolists\Components\TextEntry::make('error_message')
                ->label('Erro')
                ->visible(fn ($record) => $record->status === 'failed'),
            Infolists\Components\TextEntry::make('sent_at')->label('Enviado em')->dateTime('d/m/Y H:i:s'),
            Infolists\Components\TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i:s'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'view' => Pages\ViewNotification::route('/{record}'),
        ];
    }
}
