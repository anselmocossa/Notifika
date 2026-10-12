<?php

namespace App\Filament\Resources\ClientResource\Schemas;

use App\Models\Client;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::resumo(),
                self::identificacao(),
                self::orcamento(),
                self::credenciaisSms(),
            ]);
    }

    private static function resumo(): Section
    {
        return Section::make('Resumo')
            ->icon('heroicon-o-chart-bar')
            ->columnSpanFull()
            ->columns(4)
            ->schema([
                TextEntry::make('slug')
                    ->label('Slug')
                    ->fontFamily('mono')
                    ->copyable(),
                TextEntry::make('is_active')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Client $record): string => $record->is_active ? 'Activo' : 'Inactivo')
                    ->color(fn (Client $record): string => $record->is_active ? 'success' : 'gray'),
                TextEntry::make('notificacoes')
                    ->label('Notificações')
                    ->state(fn (Client $record): int => $record->notifications()->count()),
                TextEntry::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i'),
            ]);
    }

    private static function identificacao(): Section
    {
        return Section::make('Identificação')
            ->icon('heroicon-o-building-office-2')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                TextEntry::make('name')->label('Nome'),
                TextEntry::make('brand_name')->label('Marca')->placeholder('—'),
            ]);
    }

    private static function orcamento(): Section
    {
        return Section::make('Orçamento e estado')
            ->icon('heroicon-o-banknotes')
            ->columnSpanFull()
            ->columns(3)
            ->schema([
                TextEntry::make('monthly_budget')
                    ->label('Orçamento mensal')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state, Client $record): string => $state === null
                        ? '—'
                        : number_format((float) $state, 2, ',', '.').' '.($record->budget_currency ?: 'MZN')),
                TextEntry::make('budget_currency')
                    ->label('Moeda')
                    ->placeholder('—'),
                IconEntry::make('is_active')->label('Activo')->boolean(),
            ]);
    }

    private static function credenciaisSms(): Section
    {
        return Section::make('Credenciais de SMS')
            ->description('Os segredos ficam cifrados e não aparecem aqui.')
            ->icon('heroicon-o-key')
            ->columnSpanFull()
            ->schema([
                RepeatableEntry::make('channelCredentials')
                    ->hiddenLabel()
                    ->columns(4)
                    ->placeholder('Sem credenciais registadas.')
                    ->schema([
                        TextEntry::make('channel')
                            ->label('Canal')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === 'sms' ? 'SMS' : $state),
                        TextEntry::make('driver')
                            ->label('Driver')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('sender')
                            ->label('Remetente')
                            ->fontFamily('mono')
                            ->placeholder('—'),
                        TextEntry::make('is_active')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Activa' : 'Inactiva')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ]),
            ]);
    }
}
