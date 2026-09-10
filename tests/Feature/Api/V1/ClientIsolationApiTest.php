<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * CA-03: cada notificação pertence a um cliente; um token só vê e só envia
 * pelo seu próprio cliente.
 */
class ClientIsolationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_notification_sent_by_client_a_is_attributed_to_client_a(): void
    {
        Queue::fake();
        Mail::fake();

        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $tokenA = $clientA->createToken('a')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson('/api/v1/notifications', [
                'to' => 'destinatario@example.com',
                'content' => 'Conteúdo de teste',
            ])
            ->assertCreated();

        $notification = Notification::first();

        $this->assertSame($clientA->id, $notification->client_id);
        $this->assertNotSame($clientB->id, $notification->client_id);
    }

    public function test_client_a_token_cannot_see_client_b_notifications_in_index(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();

        $notificationB = Notification::create([
            'external_id' => Notification::generateExternalId(),
            'client_id' => $clientB->id,
            'channel' => 'email',
            'to' => 'b@example.com',
            'content' => 'De B',
            'status' => 'sent',
        ]);

        Notification::create([
            'external_id' => Notification::generateExternalId(),
            'client_id' => $clientA->id,
            'channel' => 'email',
            'to' => 'a@example.com',
            'content' => 'De A',
            'status' => 'sent',
        ]);

        $tokenA = $clientA->createToken('a')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson('/api/v1/notifications');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('external_id');

        $this->assertNotContains($notificationB->external_id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_client_a_token_cannot_see_client_b_notification_by_id(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();

        $notificationB = Notification::create([
            'external_id' => Notification::generateExternalId(),
            'client_id' => $clientB->id,
            'channel' => 'email',
            'to' => 'b@example.com',
            'content' => 'De B',
            'status' => 'sent',
        ]);

        $tokenA = $clientA->createToken('a')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson("/api/v1/notifications/{$notificationB->external_id}");

        $response->assertNotFound();
    }

    public function test_client_a_token_cannot_create_a_notification_for_client_b(): void
    {
        Queue::fake();

        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $tokenA = $clientA->createToken('a')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson('/api/v1/notifications', [
                'to' => 'destinatario@example.com',
                'content' => 'Conteúdo de teste',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseMissing('notifications', ['client_id' => $clientB->id]);
    }
}
