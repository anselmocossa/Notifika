<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Cria o cliente por omissão de uma instalação self-hosted (CA-16).
     * É a este cliente que os tokens antigos (criados antes desta leva)
     * passam a estar ligados — ver Client::fromAuthenticatable().
     *
     * Idempotente: correr o seeder outra vez não duplica o cliente.
     */
    public function run(): void
    {
        Client::firstOrCreate(
            ['slug' => config('notifika.default_client_slug', 'default')],
            [
                'name' => config('app.name', 'Notifika'),
                'brand_name' => config('app.name', 'Notifika'),
                'is_active' => true,
            ],
        );
    }
}
