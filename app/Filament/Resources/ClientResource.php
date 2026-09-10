<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $label = 'Cliente';

    protected static ?string $pluralLabel = 'Clientes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dados')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('Identificador único do cliente.'),

                    TextInput::make('brand_name')
                        ->label('Marca (brand_name)')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Vai no corpo de cada SMS deste cliente. Sem esta marca, a Twilio pode bloquear o SMS em Moçambique (CA-17).'),

                    Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),

                    TextInput::make('monthly_budget')
                        ->label('Orçamento mensal')
                        ->numeric()
                        ->nullable(),

                    TextInput::make('budget_currency')
                        ->label('Moeda do orçamento')
                        ->maxLength(3)
                        ->nullable()
                        ->placeholder('MZN'),
                ]),

            Section::make('Credenciais — SMS')
                ->columnSpanFull()
                ->description('As credenciais ficam cifradas na base de dados. Os campos de segredo nunca mostram o valor guardado e só o substituem se preenchidos de novo (CA-12).')
                ->schema([
                    Repeater::make('channelCredentials')
                        ->relationship()
                        ->label('')
                        ->addActionLabel('Adicionar credencial')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            Select::make('channel')
                                ->label('Canal')
                                ->options(['sms' => 'SMS'])
                                ->required(),

                            Select::make('driver')
                                ->label('Driver')
                                ->options([
                                    'twilio' => 'Twilio',
                                    'log' => 'Log (desenvolvimento/testes)',
                                ])
                                ->required(),

                            TextInput::make('sender')
                                ->label('Remetente (Sender ID)')
                                ->maxLength(255)
                                ->helperText('Use um sender ID alfanumérico (ex.: NOTIFIKA). Um número internacional simples não entrega SMS na Movitel (CA-18).'),

                            Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true),

                            TextInput::make('account_sid')
                                ->label('Account SID (Twilio)')
                                ->password()
                                ->revealable()
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->helperText('Deixe em branco para manter o valor já guardado.'),

                            TextInput::make('auth_token')
                                ->label('Auth Token (Twilio)')
                                ->password()
                                ->revealable()
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->helperText('Deixe em branco para manter o valor já guardado.'),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(
                            fn (array $data): array => self::mergeCredentialSecrets($data, [])
                        )
                        ->mutateRelationshipDataBeforeSaveUsing(
                            fn (array $data, Model $record): array => self::mergeCredentialSecrets($data, $record->credentials ?? [])
                        ),
                ]),
        ]);
    }

    /**
     * Os campos 'account_sid' e 'auth_token' do formulário não são colunas
     * reais de client_channel_credentials — só o JSON cifrado 'credentials'
     * é. Aqui fundem-se no array existente, preservando o que já lá estava
     * quando o campo fica em branco (CA-12: nunca sobrescrever com vazio).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $existingCredentials
     * @return array<string, mixed>
     */
    private static function mergeCredentialSecrets(array $data, array $existingCredentials): array
    {
        foreach (['account_sid', 'auth_token'] as $key) {
            if (array_key_exists($key, $data)) {
                $existingCredentials[$key] = $data[$key];
                unset($data[$key]);
            }
        }

        $data['credentials'] = $existingCredentials;

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                TextColumn::make('brand_name')
                    ->label('Marca'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('notifications_count')
                    ->label('Notificações')
                    ->counts('notifications'),

                TextColumn::make('created_at')
                    ->label('Criado')
                    ->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
