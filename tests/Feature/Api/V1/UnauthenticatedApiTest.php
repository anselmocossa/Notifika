<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CA-19: hoje um pedido sem token e sem Accept: application/json rebenta
 * com 500, porque o Laravel tenta redireccionar para a rota 'login' (que
 * não existe — o Filament regista 'filament.admin.auth.login'). Devia dar
 * sempre 401 em JSON, com ou sem esse cabeçalho, e nunca redireccionar.
 */
class UnauthenticatedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_token_returns_401_json_without_accept_header(): void
    {
        $response = $this->post('/api/v1/notifications', [
            'to' => 'destinatario@example.com',
            'content' => 'Conteúdo de teste',
        ]);

        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_missing_token_returns_401_json_with_accept_header(): void
    {
        $response = $this->postJson('/api/v1/notifications', [
            'to' => 'destinatario@example.com',
            'content' => 'Conteúdo de teste',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_get_index_without_token_and_without_accept_header_is_401_json(): void
    {
        $response = $this->get('/api/v1/notifications');

        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_never_redirects_an_unauthenticated_api_request(): void
    {
        $response = $this->post('/api/v1/notifications');

        $response->assertStatus(401);
        $this->assertFalse($response->isRedirect());
    }
}
