<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ClientResource\Pages\CreateClient;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Models\Client;
use App\Models\ClientChannelCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_page_renders_for_authenticated_admin(): void
    {
        $admin = User::factory()->create();
        Client::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListClients::class)->assertSuccessful();
    }

    public function test_create_page_renders_for_authenticated_admin(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreateClient::class)->assertSuccessful();
    }

    public function test_can_create_a_client_with_sms_credentials(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(CreateClient::class)
            ->fillForm([
                'name' => 'Cliente Teste',
                'slug' => 'cliente-teste',
                'brand_name' => 'ClienteTeste',
                'is_active' => true,
                'channelCredentials' => [
                    [
                        'channel' => 'sms',
                        'driver' => 'twilio',
                        'sender' => 'CLITESTE',
                        'is_active' => true,
                        'account_sid' => 'ACabc123',
                        'auth_token' => 'segredo-token',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clients', ['slug' => 'cliente-teste']);

        $credential = ClientChannelCredential::whereHas('client', fn ($q) => $q->where('slug', 'cliente-teste'))->first();

        $this->assertNotNull($credential);
        $this->assertSame('ACabc123', $credential->credentials['account_sid']);
        $this->assertSame('segredo-token', $credential->credentials['auth_token']);
    }

    public function test_editing_without_filling_the_secret_fields_keeps_the_stored_credentials(): void
    {
        $admin = User::factory()->create();
        $client = Client::factory()->create();
        $credential = ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'twilio',
            'credentials' => ['account_sid' => 'ACoriginal', 'auth_token' => 'token-original'],
            'sender' => 'ORIGINAL',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(EditClient::class, ['record' => $client->getRouteKey()]);

        // A chave do item do Repeater é a do registo relacionado — descobre-se
        // em vez de assumir "0", para não reescrever o formulário inteiro e
        // acabar a substituir o item em vez de o editar (fillForm() por
        // índice numérico cria um item novo e apaga o antigo).
        $key = array_key_first($component->get('data.channelCredentials'));

        // CA-12: o valor guardado nunca aparece no formulário.
        $component->assertFormSet([
            "channelCredentials.{$key}.account_sid" => null,
            "channelCredentials.{$key}.auth_token" => null,
        ]);

        $component
            ->fillForm([
                'brand_name' => 'MarcaNova',
                "channelCredentials.{$key}.sender" => 'NOVOSENDER',
                "channelCredentials.{$key}.account_sid" => null,
                "channelCredentials.{$key}.auth_token" => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $credential->refresh();

        // O sender mudou, mas o segredo que não foi reescrito continua o mesmo.
        $this->assertSame('NOVOSENDER', $credential->sender);
        $this->assertSame('ACoriginal', $credential->credentials['account_sid']);
        $this->assertSame('token-original', $credential->credentials['auth_token']);
    }

    public function test_editing_and_filling_the_secret_fields_overwrites_the_stored_credentials(): void
    {
        $admin = User::factory()->create();
        $client = Client::factory()->create();
        $credential = ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'twilio',
            'credentials' => ['account_sid' => 'ACoriginal', 'auth_token' => 'token-original'],
            'sender' => 'ORIGINAL',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(EditClient::class, ['record' => $client->getRouteKey()]);
        $key = array_key_first($component->get('data.channelCredentials'));

        $component
            ->fillForm([
                "channelCredentials.{$key}.account_sid" => 'ACnovo',
                "channelCredentials.{$key}.auth_token" => 'token-novo',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $credential->refresh();

        $this->assertSame('ACnovo', $credential->credentials['account_sid']);
        $this->assertSame('token-novo', $credential->credentials['auth_token']);
    }
}
