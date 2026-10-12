<?php

namespace App\Filament\Resources\ClientResource\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::identificacao(),
                self::orcamento(),
                self::credenciaisSms(),
            ]);
    }

    private static function identificacao(): Section
    {
        return Section::make('Identificação')
            ->description('O nome com que o cliente aparece no painel e o identificador usado na API.')
            ->icon('heroicon-o-building-office-2')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, Set $set, ?string $state): void {
                        if ($operation !== 'create') {
                            return;
                        }

                        $set('slug', Str::slug((string) $state));
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Minúsculas, números e hífenes. Gerado a partir do nome.')
                    ->disabledOn('edit')
                    ->hint(fn (string $operation): ?string => $operation === 'edit' ? 'Não se altera' : null),
                TextInput::make('brand_name')
                    ->label('Marca')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->helperText('Vai no corpo de cada SMS deste cliente. Sem esta marca, a Twilio pode bloquear o SMS em Moçambique.'),
            ]);
    }

    private static function orcamento(): Section
    {
        return Section::make('Orçamento e estado')
            ->description('O tecto mensal de consumo e se o cliente pode enviar.')
            ->icon('heroicon-o-banknotes')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                TextInput::make('monthly_budget')
                    ->label('Orçamento mensal')
                    ->numeric()
                    ->minValue(0)
                    ->nullable(),
                TextInput::make('budget_currency')
                    ->label('Moeda do orçamento')
                    ->maxLength(3)
                    ->nullable()
                    ->placeholder('MZN'),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->inline(false),
            ]);
    }

    private static function credenciaisSms(): Section
    {
        return Section::make('Credenciais de SMS')
            ->description('Ficam cifradas na base de dados. Os segredos nunca mostram o valor guardado e só o substituem se forem preenchidos de novo.')
            ->icon('heroicon-o-key')
            ->columnSpanFull()
            ->schema([
                Repeater::make('channelCredentials')
                    ->relationship()
                    ->hiddenLabel()
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
                                'log' => 'Log (desenvolvimento)',
                            ])
                            ->required(),
                        TextInput::make('sender')
                            ->label('Remetente')
                            ->maxLength(255)
                            ->helperText('Sender ID alfanumérico (ex.: NOTIFIKA). Um número internacional simples não entrega SMS na Movitel.'),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('account_sid')
                            ->label('Account SID')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Deixe em branco para manter o valor já guardado.'),
                        TextInput::make('auth_token')
                            ->label('Auth Token')
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
            ]);
    }

    /**
     * Os campos account_sid e auth_token não são colunas de
     * client_channel_credentials — só o JSON cifrado credentials é. Fundem-se
     * no array existente e um campo em branco não apaga o que já lá estava.
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
}
