<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * CA-01 / CA-16: antes desta leva, os tokens Sanctum pertenciam a um User —
 * é assim que qualquer integração já existente foi emitida. Essa integração
 * não pode notar diferença nenhuma: o token continua a autenticar, e a
 * notificação passa a ficar atribuída ao cliente por omissão da instalação
 * (Client::fromAuthenticatable — ver app/Models/Client.php).
 */
class LegacyTokenCompatibilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_legacy_user_bound_token_still_authenticates_and_sends(): void
    {
        Queue::fake();
        Mail::fake();

        $user = User::factory()->create();
        $legacyToken = $user->createToken('integração-antiga')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$legacyToken}")
            ->postJson('/api/v1/notifications', [
                'to' => 'destinatario@example.com',
                'subject' => 'Assunto de teste',
                'content' => 'Conteúdo de teste',
            ]);

        $response->assertCreated();
        $response->assertJson(['status' => 'queued', 'channel' => 'email']);
    }

    public function test_a_legacy_user_bound_token_is_attributed_to_the_default_client(): void
    {
        Queue::fake();
        Mail::fake();

        $user = User::factory()->create();
        $legacyToken = $user->createToken('integração-antiga')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$legacyToken}")
            ->postJson('/api/v1/notifications', [
                'to' => 'destinatario@example.com',
                'content' => 'Conteúdo de teste',
            ])
            ->assertCreated();

        $notification = Notification::first();
        $defaultClient = Client::default();

        $this->assertSame($defaultClient->id, $notification->client_id);
    }

    public function test_a_legacy_token_only_sees_default_client_notifications(): void
    {
        $user = User::factory()->create();
        $legacyToken = $user->createToken('integração-antiga')->plainTextToken;

        $otherClient = Client::factory()->create();
        Notification::create([
            'external_id' => Notification::generateExternalId(),
            'client_id' => $otherClient->id,
            'channel' => 'email',
            'to' => 'outro@example.com',
            'content' => 'De outro cliente',
            'status' => 'sent',
        ]);

        $defaultClient = Client::default();
        $own = Notification::create([
            'external_id' => Notification::generateExternalId(),
            'client_id' => $defaultClient->id,
            'channel' => 'email',
            'to' => 'proprio@example.com',
            'content' => 'Do cliente por omissão',
            'status' => 'sent',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$legacyToken}")
            ->getJson('/api/v1/notifications');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('external_id');

        $this->assertContains($own->external_id, $ids);
        $this->assertCount(1, $ids);
    }
}
