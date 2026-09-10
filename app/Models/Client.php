<?php

namespace App\Models;

use Database\Factories\ClientFactory;
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
     * O cliente por omissão de uma instalação self-hosted (CA-16). É criado
     * pelo seeder com o slug configurado em notifika.default_client_slug.
     */
    public static function default(): self
    {
        $slug = Config::string('notifika.default_client_slug', 'default');

        return static::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Resolve o cliente "dono" de um pedido autenticado. Os tokens novos
     * pertencem directamente a um Client; os tokens antigos (criados antes
     * desta leva) continuam ligados a um User e são atribuídos ao cliente
     * por omissão — é assim que "os tokens antigos continuam a funcionar"
     * (CA-01, CA-16) sem quebrar nenhuma integração existente.
     */
    public static function fromAuthenticatable(mixed $authenticatable): self
    {
        if ($authenticatable instanceof self) {
            return $authenticatable;
        }

        return static::default();
    }

    public function credentialFor(string $channel): ?ClientChannelCredential
    {
        return $this->channelCredentials
            ->firstWhere(fn (ClientChannelCredential $credential) => $credential->channel === $channel && $credential->is_active);
    }
}
