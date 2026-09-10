<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\HasApiTokens;

/**
 * @use HasFactory<ClientFactory>
 */
class Client extends Model
{
    use HasApiTokens, HasFactory, HasUuids;

    protected $fillable = [
        'name', 'slug', 'brand_name', 'is_active', 'monthly_budget', 'budget_currency',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monthly_budget' => 'decimal:2',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function channelCredentials(): HasMany
    {
        return $this->hasMany(ClientChannelCredential::class);
    }

    /**
     * O cliente por omissão de uma instalação self-hosted (CA-16). O
     * ClientSeeder já o cria à partida, mas esta chamada é auto-suficiente
     * (firstOrCreate) — uma instalação que nunca correu o seeder, ou uma
     * base de testes sem seeding, continuam a funcionar "do zero".
     */
    public static function default(): self
    {
        $slug = Config::string('notifika.default_client_slug', 'default');

        return static::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => Config::string('app.name', 'Notifika'),
                'brand_name' => Config::string('app.name', 'Notifika'),
                'is_active' => true,
            ],
        );
    }

    /**
     * Resolve o cliente "dono" de um pedido autenticado. Desde a migração
     * `2026_09_10_100005_convert_personal_access_tokens_tokenable_id_to_string`,
     * todo o token Sanctum (novo ou antigo, já reatribuído ao cliente por
     * omissão — CA-01, CA-16) pertence a um Client: na API, o autenticado é
     * sempre um Client.
     *
     * O `User` já não tem `HasApiTokens`, mas a guarda do Sanctum inclui o
     * guard `web` (sessão) antes do bearer token — por isso um pedido feito
     * com uma sessão de painel activa ainda pode chegar aqui com um `User`.
     * Isso nunca deve dar 500: rejeita-se como 403 em JSON.
     */
    public static function fromAuthenticatable(mixed $authenticatable): self
    {
        if ($authenticatable instanceof self) {
            return $authenticatable;
        }

        throw new AuthorizationException('O autenticado não é um cliente da API.');
    }

    public function credentialFor(string $channel): ?ClientChannelCredential
    {
        return $this->channelCredentials
            ->firstWhere(fn (ClientChannelCredential $credential) => $credential->channel === $channel && $credential->is_active);
    }
}
