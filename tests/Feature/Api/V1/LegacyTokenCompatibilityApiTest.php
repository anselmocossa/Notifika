<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CA-01 / CA-16: antes da migração
 * `2026_09_10_100005_convert_personal_access_tokens_tokenable_id_to_string`,
 * os tokens Sanctum pertenciam a um User — é assim que qualquer integração já
 * existente foi emitida (`tokenable_type` = App\Models\User, `tokenable_id`
 * bigint). Essa integração não pode notar diferença nenhuma depois do
 * upgrade: o token continua a autenticar, com o mesmo hash, e a notificação
 * passa a ficar atribuída ao cliente por omissão da instalação.
 *
 * O `User` já não tem HasApiTokens (só o Client emite tokens agora), por
 * isso não há como pedir a um `User::factory()` um token "à moda antiga" —
 * o teste tem de recriar o esquema antigo a sério: reverte a migração
 * (a tabela está vazia, o down() deixa reverter — ver o guard no próprio
 * down()), insere o token directamente com um tokenable_id inteiro, como
 * o Sanctum fazia, e só depois corre a migração outra vez. É essa migração
 * que reatribui o token ao cliente por omissão, sem tocar no hash.
 */
class LegacyTokenCompatibilityApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recria um token "à moda antiga": um User, com um token cujo
     * tokenable_type/tokenable_id apontam para ele no esquema anterior a
     * esta leva (tokenable_id bigint). Corre a seguir a migração que faz a
     * transferência para o cliente por omissão, tal como aconteceria numa
     * instalação real ao fazer o upgrade.
     */
    private function issueLegacyUserToken(User $user, string $name = 'integração-antiga'): string
    {
        Artisan::call('migrate:rollback', ['--step' => 1]);

        $plainTextToken = Str::random(40);

        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('migrate');

        return $tokenId.'|'.$plainTextToken;
    }

    public function test_a_legacy_user_bound_token_still_authenticates_and_sends(): void
    {
        Queue::fake();
        Mail::fake();

        $user = User::factory()->create();
        $legacyToken = $this->issueLegacyUserToken($user);

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
        $legacyToken = $this->issueLegacyUserToken($user);

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
        $legacyToken = $this->issueLegacyUserToken($user);

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

    /**
     * O hash do token (coluna "token") não muda quando a migração transfere
     * a posse para o cliente por omissão — é essa a garantia de que uma
     * integração já emitida continua a funcionar com o mesmo valor.
     */
    public function test_migrating_a_legacy_token_keeps_its_hash(): void
    {
        $user = User::factory()->create();
        $plainTextToken = Str::random(40);
        $expectedHash = hash('sha256', $plainTextToken);

        Artisan::call('migrate:rollback', ['--step' => 1]);

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'integração-antiga',
            'token' => $expectedHash,
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('migrate');

        $row = DB::table('personal_access_tokens')->where('token', $expectedHash)->first();

        $this->assertNotNull($row, 'O hash do token não sobreviveu à migração.');
        $this->assertSame('App\\Models\\Client', $row->tokenable_type);
        $this->assertSame(Client::default()->id, $row->tokenable_id);
    }
}
