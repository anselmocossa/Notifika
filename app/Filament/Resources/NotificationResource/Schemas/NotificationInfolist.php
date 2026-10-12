<?php

namespace App\Filament\Resources\NotificationResource\Schemas;

use App\Models\Notification;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::resumo(),
                self::mensagem(),
                self::entrega(),
            ]);
    }

    private static function resumo(): Section
    {
        return Section::make('Resumo')
            ->icon('heroicon-o-bell')
            ->columnSpanFull()
            ->columns(4)
            ->schema([
                TextEntry::make('external_id')
                    ->label('ID')
                    ->fontFamily('mono')
                    ->copyable(),
                TextEntry::make('client.name')
                    ->label('Cliente')
                    ->placeholder('—')
                    ->badge(),
                TextEntry::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::rotuloCanal($state))
                    ->color(fn (Notification $record): string => self::corCanal($record->channel)),
                TextEntry::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::rotuloEstado($state))
                    ->color(fn (Notification $record): string => self::corEstado($record->status)),
            ]);
    }

    private static function mensagem(): Section
    {
        return Section::make('Mensagem')
            ->icon('heroicon-o-envelope')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                TextEntry::make('to')
                    ->label('Destinatário')
                    ->copyable(),
                TextEntry::make('template.name')
                    ->label('Modelo')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),
                TextEntry::make('subject')
                    ->label('Assunto')
                    ->placeholder('—')
                    ->visible(fn (Notification $record): bool => $record->channel === 'email')
                    ->columnSpanFull(),
                TextEntry::make('content')
                    ->label('Conteúdo')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }

    private static function entrega(): Section
    {
        return Section::make('Entrega')
            ->icon('heroicon-o-paper-airplane')
            ->columnSpanFull()
            ->columns(3)
            ->schema([
                TextEntry::make('driver')->label('Driver')->placeholder('—'),
                TextEntry::make('attempts')->label('Tentativas'),
                TextEntry::make('encoding')->label('Codificação')->placeholder('—'),
                TextEntry::make('segments')->label('Segmentos')->placeholder('—'),
                TextEntry::make('provider_message_id')
                    ->label('ID do fornecedor')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->copyable(),
                TextEntry::make('cost')
                    ->label('Custo')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, Notification $record): string => $state !== null
                        ? number_format((float) $state, 4, ',', '.').' '.($record->cost_currency ?? '')
                        : '—'),
                TextEntry::make('sent_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->placeholder('—'),
                TextEntry::make('failed_at')
                    ->label('Falhou em')
                    ->dateTime('d/m/Y H:i:s')
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s'),
                TextEntry::make('error_message')
                    ->label('Erro')
                    ->color('danger')
                    ->visible(fn (Notification $record): bool => $record->status === 'failed')
                    ->columnSpanFull(),
            ]);
    }

    private static function rotuloCanal(?string $canal): string
    {
        return match ($canal) {
            'email' => 'E-mail',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            default => $canal ?: '—',
        };
    }

    private static function corCanal(?string $canal): string
    {
        return match ($canal) {
            'email' => 'info',
            'sms' => 'warning',
            'whatsapp' => 'success',
            default => 'gray',
        };
    }

    private static function rotuloEstado(?string $estado): string
    {
        return match ($estado) {
            'queued' => 'Na fila',
            'processing' => 'A processar',
            'sent' => 'Enviado',
            'failed' => 'Falhado',
            default => $estado ?: '—',
        };
    }

    private static function corEstado(?string $estado): string
    {
        return match ($estado) {
            'queued' => 'gray',
            'processing' => 'warning',
            'sent' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }
}
