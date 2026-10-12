<?php

namespace App\Filament\Resources\TemplateResource\Schemas;

use App\Models\Template;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::resumo(),
                self::conteudo(),
                self::estado(),
            ]);
    }

    private static function resumo(): Section
    {
        return Section::make('Resumo')
            ->icon('heroicon-o-chart-bar')
            ->columnSpanFull()
            ->columns(4)
            ->schema([
                TextEntry::make('name')
                    ->label('Slug')
                    ->fontFamily('mono')
                    ->copyable(),
                TextEntry::make('channel')
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
                    }),
                TextEntry::make('is_active')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Template $record): string => $record->is_active ? 'Activo' : 'Inactivo')
                    ->color(fn (Template $record): string => $record->is_active ? 'success' : 'gray'),
                TextEntry::make('envios')
                    ->label('Envios')
                    ->state(fn (Template $record): int => $record->notifications()->count()),
            ]);
    }

    private static function conteudo(): Section
    {
        return Section::make('Conteúdo')
            ->icon('heroicon-o-document-text')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                TextEntry::make('display_name')->label('Nome'),
                TextEntry::make('client.name')
                    ->label('Cliente')
                    ->placeholder('Todos os clientes'),
                TextEntry::make('subject')
                    ->label('Assunto do e-mail')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('content')
                    ->label('Conteúdo')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('variables')
                    ->label('Variáveis')
                    ->badge()
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }

    private static function estado(): Section
    {
        return Section::make('Estado')
            ->icon('heroicon-o-adjustments-horizontal')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                IconEntry::make('is_active')->label('Activo')->boolean(),
                TextEntry::make('updated_at')
                    ->label('Actualizado em')
                    ->dateTime('d/m/Y H:i'),
            ]);
    }
}
