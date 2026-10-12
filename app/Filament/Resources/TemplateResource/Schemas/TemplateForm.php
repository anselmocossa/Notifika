<?php

namespace App\Filament\Resources\TemplateResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::identificacao(),
                self::conteudo(),
                self::estado(),
            ]);
    }

    private static function identificacao(): Section
    {
        return Section::make('Identificação')
            ->description('O slug é o identificador que a API recebe no campo template.')
            ->icon('heroicon-o-document-text')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                TextInput::make('display_name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Confirmação de Pagamento'),
                TextInput::make('name')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('payment_confirmed')
                    ->helperText('Identificador único usado na API.'),
                Select::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Todos os clientes')
                    ->helperText('Vazio: o modelo fica disponível a todos os clientes.'),
                Select::make('channel')
                    ->label('Canal')
                    ->options([
                        'email' => 'E-mail',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                    ])
                    ->default('email')
                    ->required(),
            ]);
    }

    private static function conteudo(): Section
    {
        return Section::make('Conteúdo')
            ->description('Use {{ variavel }} para os dados que chegam no pedido.')
            ->icon('heroicon-o-pencil-square')
            ->columnSpanFull()
            ->schema([
                TextInput::make('subject')
                    ->label('Assunto do e-mail')
                    ->maxLength(255)
                    ->placeholder('Pagamento confirmado - Pedido {{ order_id }}')
                    ->helperText('Só é usado no canal de e-mail.'),
                Textarea::make('content')
                    ->label('Conteúdo')
                    ->required()
                    ->rows(8)
                    ->placeholder('Olá {{ name }}, o seu pagamento de {{ amount }} foi confirmado.'),
                TagsInput::make('variables')
                    ->label('Variáveis')
                    ->placeholder('Adicionar variável')
                    ->helperText('Variáveis disponíveis, por exemplo name e amount.'),
            ]);
    }

    private static function estado(): Section
    {
        return Section::make('Estado')
            ->description('Modelos inactivos deixam de poder ser usados em envios novos.')
            ->icon('heroicon-o-adjustments-horizontal')
            ->columnSpanFull()
            ->schema([
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->inline(false),
            ]);
    }
}
