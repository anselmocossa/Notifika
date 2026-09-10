<?php

return [
    'max_attempts' => env('NOTIFIKA_MAX_ATTEMPTS', 3),
    'queue' => env('NOTIFIKA_QUEUE', 'notifications'),

    // Slug do cliente por omissão de uma instalação self-hosted (CA-16).
    // É a este cliente que os tokens antigos (criados antes das credenciais
    // por cliente) ficam ligados — ver Client::fromAuthenticatable().
    'default_client_slug' => env('NOTIFIKA_DEFAULT_CLIENT_SLUG', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Canais — configuração da instalação
    |--------------------------------------------------------------------------
    |
    | O ChannelManager resolve o driver de um canal por esta ordem (CA-04):
    |   1. credenciais do cliente (tabela client_channel_credentials)
    |   2. estas credenciais da instalação (.env)
    |   3. erro 422 "canal não configurado"
    |
    | 'driver' a null/vazio significa "não configurado nesta instalação" —
    | é isso que faz o passo 2 falhar e cair no 422 quando também não há
    | credenciais de cliente. O e-mail já vem configurado, para nenhuma
    | integração actual notar diferença (CA-01).
    |
    */
    'channels' => [
        'email' => [
            'driver' => env('NOTIFIKA_EMAIL_DRIVER', 'mail'),
        ],

        'sms' => [
            'driver' => env('NOTIFIKA_SMS_DRIVER'),
            // Sender ID alfanumérico (recomendado registar na Twilio para MZ — SDD §2).
            'sender' => env('NOTIFIKA_SMS_SENDER'),
            'credentials' => [
                'account_sid' => env('TWILIO_ACCOUNT_SID'),
                'auth_token' => env('TWILIO_AUTH_TOKEN'),
            ],
        ],

        // WhatsApp fica pronto para a leva 2 (Meta Cloud API / Twilio) — sem
        // driver nesta leva, qualquer pedido resolve para 422.
        'whatsapp' => [
            'driver' => env('NOTIFIKA_WHATSAPP_DRIVER'),
        ],
    ],
];
