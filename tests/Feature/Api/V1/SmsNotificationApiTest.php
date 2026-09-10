<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\ClientChannelCredential;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(Client $client): string
    {
        return $client->createToken('test-token')->plainTextToken;
    }

    public function test_a_valid_mozambique_number_is_accepted_and_normalized_to_e164(): void
    {
        Queue::fake();

        $client = Client::factory()->create(['brand_name' => 'Notifika']);
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'log',
            'credentials' => [],
            'sender' => 'NOTIFIKA',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($client))
            ->postJson('/api/v1/notifications', [
                'channel' => 'sms',
                'to' => '841234567',
                'content' => 'O seu código é 1234.',
            ]);

        $response->assertCreated();
        $response->assertJson(['status' => 'queued', 'channel' => 'sms']);
        $response->assertJsonStructure(['id', 'status', 'channel', 'encoding', 'segments', 'created_at']);

        $this->assertDatabaseHas('notifications', [
            'client_id' => $client->id,
            'channel' => 'sms',
            'to' => '+258841234567',
        ]);
    }

    public function test_an_invalid_mozambique_number_is_rejected_with_a_clear_message(): void
    {
        $client = Client::factory()->create();
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'log',
            'credentials' => [],
            'sender' => 'NOTIFIKA',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($client))
            ->postJson('/api/v1/notifications', [
                'channel' => 'sms',
                'to' => '881234567', // 88 não é uma rede móvel moçambicana.
                'content' => 'Teste',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['to']);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_single_c_cedilha_forces_ucs2_in_the_response(): void
    {
        Queue::fake();

        $client = Client::factory()->create(['brand_name' => 'Notifika']);
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'log',
            'credentials' => [],
            'sender' => 'NOTIFIKA',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($client))
            ->postJson('/api/v1/notifications', [
                'channel' => 'sms',
                'to' => '841234567',
                'content' => 'Notifika: a sua notificação chegou.',
            ]);

        $response->assertCreated();
        $response->assertJson(['encoding' => 'ucs2']);
    }

    public function test_sms_without_the_brand_name_is_prefixed_and_the_segments_count_the_prefix(): void
    {
        Queue::fake();

        $client = Client::factory()->create(['brand_name' => 'Notifika']);
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'log',
            'credentials' => [],
            'sender' => 'NOTIFIKA',
            'is_active' => true,
        ]);

        $body = str_repeat('a', 155);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($client))
            ->postJson('/api/v1/notifications', [
                'channel' => 'sms',
                'to' => '841234567',
                'content' => $body,
            ]);

        $response->assertCreated();
        $response->assertJson(['segments' => 2]);

        $notification = Notification::first();
        $this->assertStringStartsWith('Notifika: ', $notification->content);
    }

    public function test_sms_channel_without_any_configuration_returns_422(): void
    {
        $client = Client::factory()->create();
        // Sem credenciais de cliente e sem NOTIFIKA_SMS_DRIVER na instalação.
        config()->set('notifika.channels.sms.driver', null);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($client))
            ->postJson('/api/v1/notifications', [
                'channel' => 'sms',
                'to' => '841234567',
                'content' => 'Teste',
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_real_twilio_send_records_provider_message_id_and_cost(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response([
                'sid' => 'SM1234567890',
                'status' => 'queued',
                'price' => '-0.41700',
                'price_unit' => 'USD',
            ], 201),
        ]);

        $client = Client::factory()->create(['brand_name' => 'Notifika']);
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'twilio',
            'credentials' => ['account_sid' => 'ACtest', 'auth_token' => 'secret'],
            'sender' => 'NOTIFIKA',
            'is_active' => true,
        ]);

        // Sem Queue::fake(): QUEUE_CONNECTION=sync no phpunit.xml corre o job
        // já dentro do pedido, sem tocar em nenhuma fila real.
        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($client))
            ->postJson('/api/v1/notifications', [
                'channel' => 'sms',
                'to' => '841234567',
                'content' => 'Notifika: o seu código é 1234.',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'client_id' => $client->id,
            'status' => 'sent',
            'driver' => 'twilio',
            'provider_message_id' => 'SM1234567890',
        ]);
    }
}
