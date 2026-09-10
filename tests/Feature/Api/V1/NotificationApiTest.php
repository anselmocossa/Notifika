<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendNotificationJob;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_token_can_send_a_notification(): void
    {
        Queue::fake();
        Mail::fake();

        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/notifications', [
                'to' => 'destinatario@example.com',
                'subject' => 'Assunto de teste',
                'content' => 'Conteúdo de teste',
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['id', 'status', 'created_at']);
        $response->assertJson(['status' => 'queued']);

        $this->assertDatabaseHas('notifications', [
            'to' => 'destinatario@example.com',
            'status' => 'queued',
        ]);

        Queue::assertPushed(SendNotificationJob::class);
        Mail::assertNothingSent();
    }

    public function test_a_request_without_a_token_is_rejected(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications', [
            'to' => 'destinatario@example.com',
            'subject' => 'Assunto de teste',
            'content' => 'Conteúdo de teste',
        ]);

        $response->assertUnauthorized();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_request_with_an_invalid_token_is_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer token-invalido')
            ->postJson('/api/v1/notifications', [
                'to' => 'destinatario@example.com',
                'content' => 'Conteúdo de teste',
            ]);

        $response->assertUnauthorized();
    }
}
