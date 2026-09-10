<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use App\Filament\Resources\TemplateResource\Pages\ListTemplates;
use App\Filament\Resources\TemplateResource\Pages\CreateTemplate;
use App\Filament\Resources\TemplateResource\Pages\EditTemplate;
use App\Filament\Resources\TemplateResource\Pages;
use App\Models\Template;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Configurações';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->placeholder('payment_confirmed')
                ->helperText('Identificador único usado na API'),

            TextInput::make('display_name')
                ->label('Nome')
                ->required()
                ->placeholder('Confirmação de Pagamento'),

            TextInput::make('subject')
                ->label('Assunto do Email')
                ->placeholder('Pagamento confirmado - Pedido {{ order_id }}'),

            Textarea::make('content')
                ->label('Conteúdo')
                ->required()
                ->rows(8)
                ->placeholder('Olá {{ name }}, o seu pagamento de {{ amount }} foi confirmado.'),

            TagsInput::make('variables')
                ->label('Variáveis')
                ->placeholder('Adicionar variável')
                ->helperText('Variáveis disponíveis: {{ name }}, {{ amount }}, etc.'),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Slug')->searchable(),
                TextColumn::make('display_name')->label('Nome')->searchable(),
                TextColumn::make('subject')->label('Assunto')->limit(40),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                TextColumn::make('notifications_count')
                    ->label('Envios')
                    ->counts('notifications'),
                TextColumn::make('created_at')->label('Criado')->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }
}
