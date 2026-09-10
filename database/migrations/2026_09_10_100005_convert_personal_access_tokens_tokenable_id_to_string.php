<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Os tokens de API passam a pertencer só a App\Models\Client (SDD §4).
     * O Sanctum cria "tokenable_id" como bigint (morphs()), pensado para
     * chaves incrementais — mas o Client usa UUID (HasUuids). Em
     * PostgreSQL, a base de produção, guardar um UUID nesse bigint rebenta
     * com SQLSTATE 22P02 "invalid input syntax for type bigint". Em SQLite
     * :memory: (onde a suite corria até agora) o tipo não é imposto e o
     * defeito não aparecia — só foi encontrado num E2E real contra
     * PostgreSQL.
     *
     * Esta migração:
     *  1. Alarga "tokenable_id" para string(36) — cabe um UUID e continua
     *     a caber, em texto, um id inteiro de User.
     *  2. Transfere os tokens antigos (tokenable_type = App\Models\User)
     *     para o cliente por omissão da instalação
     *     (config('notifika.default_client_slug')), criando-o se ainda
     *     não existir. O hash do token (coluna "token") não é tocado — uma
     *     integração já emitida continua a autenticar com o mesmo token
     *     depois desta migração (CA-01, CA-16).
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Mantém o índice composto (tokenable_type, tokenable_id) criado
            // por morphs() — o ->change() só troca o tipo da coluna.
            $table->string('tokenable_id', 36)->change();
        });

        $legacyType = 'App\\Models\\User';
        $clientType = 'App\\Models\\Client';

        $hasLegacyTokens = DB::table('personal_access_tokens')
            ->where('tokenable_type', $legacyType)
            ->exists();

        if (! $hasLegacyTokens) {
            return;
        }

        $slug = config('notifika.default_client_slug', 'default');

        $clientId = DB::table('clients')->where('slug', $slug)->value('id');

        if (! $clientId) {
            $clientId = (string) Str::uuid7();
            $now = now();

            DB::table('clients')->insert([
                'id' => $clientId,
                'name' => config('app.name', 'Notifika'),
                'slug' => $slug,
                'brand_name' => config('app.name', 'Notifika'),
                'is_active' => true,
                'monthly_budget' => null,
                'budget_currency' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('personal_access_tokens')
            ->where('tokenable_type', $legacyType)
            ->update([
                'tokenable_type' => $clientType,
                'tokenable_id' => $clientId,
            ]);
    }

    /**
     * O retrocesso não é seguro em geral: depois do up(), os tokens
     * migrados (e qualquer token de cliente criado desde então) guardam um
     * UUID em "tokenable_id". Reduzir a coluna a bigint outra vez rebentava
     * exactamente com o mesmo erro 22P02, ao contrário, e perdia esses
     * tokens. Por isso o down() recusa-se a correr sempre que existir
     * QUALQUER token pertencente a um Client — só reverte a coluna quando a
     * tabela está "limpa" desse tipo (por exemplo, logo a seguir ao up(),
     * antes de qualquer novo login/token).
     */
    public function down(): void
    {
        $clientType = 'App\\Models\\Client';

        $hasClientTokens = DB::table('personal_access_tokens')
            ->where('tokenable_type', $clientType)
            ->exists();

        if ($hasClientTokens) {
            throw new RuntimeException(
                'Não é possível reverter esta migração: existem tokens pertencentes a um Client (UUID) em '.
                '"personal_access_tokens". Reduzir "tokenable_id" para bigint outra vez perderia esses tokens. '.
                'Restaure a partir de uma cópia de segurança se precisar mesmo de voltar ao esquema antigo.'
            );
        }

        // O PostgreSQL não converte varchar → bigint sem um USING explícito
        // ("column cannot be cast automatically to type bigint"), mesmo com
        // a tabela vazia — ao contrário do sentido inverso (up()), que o
        // Postgres aceita implicitamente. O Blueprint ->change() do Laravel
        // não gera esse USING, por isso o downgrade em Postgres precisa de
        // SQL nativo; SQLite e MySQL toleram a alteração pelo Blueprint.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE bigint USING tokenable_id::bigint');
            DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id SET NOT NULL');
        } else {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('tokenable_id')->change();
            });
        }
    }
};
