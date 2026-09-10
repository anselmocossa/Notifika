<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O guard do Sanctum inclui o guard "web" (sessão) antes do bearer token
 * (config/sanctum.php). O User já não tem HasApiTokens, mas continua a
 * poder autenticar-se no painel por sessão — por isso um pedido à API feito
 * com essa sessão activa ainda pode chegar ao controller com um User em vez
 * de um Client. Client::fromAuthenticatable() tem de recusar isso como 403
 * em JSON, nunca como 500 (ver app/Models/Client.php).
 */
class NonClientAuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_session_authenticated_user_gets_403_json_instead_of_500(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/notifications');

        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
    }
}
