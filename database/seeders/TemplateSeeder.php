<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        Template::create([
            'name' => 'welcome',
            'display_name' => 'Boas-vindas',
            'subject' => 'Bem-vindo ao {{ app_name }}!',
            'content' => 'Olá {{ name }}, bem-vindo ao {{ app_name }}! A sua conta foi criada com sucesso.',
            'variables' => ['name', 'app_name'],
        ]);

        Template::create([
            'name' => 'payment_confirmed',
            'display_name' => 'Pagamento Confirmado',
            'subject' => 'Pagamento confirmado - Pedido {{ order_id }}',
            'content' => 'Olá {{ name }}, o seu pagamento de {{ amount }} referente ao pedido {{ order_id }} foi confirmado com sucesso. Obrigado pela sua compra!',
            'variables' => ['name', 'amount', 'order_id'],
        ]);

        Template::create([
            'name' => 'password_reset',
            'display_name' => 'Recuperação de Password',
            'subject' => 'Recuperar a sua password',
            'content' => 'Olá {{ name }}, recebemos um pedido para recuperar a sua password. Use o código {{ code }} para continuar. Este código expira em 15 minutos.',
            'variables' => ['name', 'code'],
        ]);
    }
}
