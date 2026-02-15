<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateResource\Pages;
use App\Models\Template;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Configurações';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->placeholder('payment_confirmed')
                ->helperText('Identificador único usado na API'),

            Forms\Components\TextInput::make('display_name')
                ->label('Nome')
                ->required()
                ->placeholder('Confirmação de Pagamento'),

            Forms\Components\TextInput::make('subject')
                ->label('Assunto do Email')
                ->placeholder('Pagamento confirmado - Pedido {{ order_id }}'),

            Forms\Components\Textarea::make('content')
                ->label('Conteúdo')
                ->required()
                ->rows(8)
                ->placeholder('Olá {{ name }}, o seu pagamento de {{ amount }} foi confirmado.'),

            Forms\Components\TagsInput::make('variables')
                ->label('Variáveis')
                ->placeholder('Adicionar variável')
                ->helperText('Variáveis disponíveis: {{ name }}, {{ amount }}, etc.'),

            Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Slug')->searchable(),
                Tables\Columns\TextColumn::make('display_name')->label('Nome')->searchable(),
                Tables\Columns\TextColumn::make('subject')->label('Assunto')->limit(40),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('notifications_count')
                    ->label('Envios')
                    ->counts('notifications'),
                Tables\Columns\TextColumn::make('created_at')->label('Criado')->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
