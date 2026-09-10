<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendNotificationJob;
use App\Models\Client;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Confirms that the Laravel 13 CSRF rename (VerifyCsrfToken -> PreventRequestForgery)
 * did not pull request-forgery protection into the token-authenticated API.
 */
class NotificationApiCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_v1_notifications_route_is_not_guarded_by_csrf_middleware(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/notifications' && in_array('POST', $route->methods(), true)
        );

        $this->assertNotNull($route, 'A rota POST api/v1/notifications não foi encontrada.');

        $middleware = $route->gatherMiddleware();

        $this->assertNotContains(PreventRequestForgery::class, $middleware);
        $this->assertNotContains('web', $middleware);
        $this->assertContains('api', $middleware);
    }

    public function test_bearer_token_request_without_csrf_token_is_accepted(): void
    {
        Queue::fake();
        Mail::fake();

        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;

        // Nem _token no corpo, nem cabeçalhos X-CSRF-TOKEN / X-XSRF-TOKEN.
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/notifications', [
                'to' => 'destinatario@example.com',
                'subject' => 'Assunto de teste',
                'content' => 'Conteúdo de teste',
            ]);

        $response->assertCreated();

        Queue::assertPushed(SendNotificationJob::class);
    }
}
