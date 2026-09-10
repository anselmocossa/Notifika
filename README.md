# Notifika

Plataforma de Notificações Multicanal Open Source para Moçambique e mercados emergentes.

**Stack:** Laravel 12.69 + Filament 5.8 + Livewire 4.4 + PostgreSQL + Redis

## Funcionalidades (MVP)

- **API REST** para envio de notificações por email (SMTP/SendGrid)
- **Sistema de Templates** com variáveis dinâmicas (`{{ name }}`, `{{ amount }}`, etc.)
- **Fila de mensagens** com Redis, retries e backoff exponencial
- **Dashboard Filament** com estatísticas em tempo real, gestão de templates e logs
- **Autenticação API** via Laravel Sanctum (tokens)

## Requisitos

- PHP 8.2+
- Composer
- PostgreSQL
- Redis
- Node.js (para assets)

## Instalação

```bash
# 1. Clonar o repositório
git clone https://github.com/anselmocossa/Notifika.git
cd Notifika

# 2. Instalar dependências PHP
composer install

# 3. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 4. Configurar .env com as credenciais da base de dados e email

# 5. Criar base de dados PostgreSQL
createdb notifika

# 6. Executar migrações e seeders
php artisan migrate
php artisan db:seed

# 7. Publicar assets do Filament
php artisan filament:install --panels
```

## Utilização

### Iniciar o servidor

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Queue worker
php artisan queue:work --queue=notifications
```

### Aceder ao Dashboard

Abrir `http://localhost:8000/admin` e fazer login:
- **Email:** admin@notifika.co.mz
- **Password:** password

### Gerar Token API

```bash
php artisan tinker
> $user = \App\Models\User::first();
> echo $user->createToken('notifika-api')->plainTextToken;
```

### Enviar Notificação via API

**Com template:**
```bash
curl -X POST http://localhost:8000/api/v1/notifications \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "joao@email.com",
    "template": "payment_confirmed",
    "data": {
      "name": "João Silva",
      "amount": "1.500,00 MT",
      "order_id": "ORD-001"
    }
  }'
```

**Sem template (conteúdo direto):**
```bash
curl -X POST http://localhost:8000/api/v1/notifications \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "maria@email.com",
    "subject": "Teste Notifika",
    "content": "Este é um teste do sistema Notifika."
  }'
```

**Verificar status:**
```bash
curl http://localhost:8000/api/v1/notifications/{id} \
  -H "Authorization: Bearer {TOKEN}"
```

## API Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/v1/notifications` | Enviar notificação |
| GET | `/api/v1/notifications` | Listar notificações |
| GET | `/api/v1/notifications/{id}` | Ver status de notificação |

## Estrutura do Projeto

```
app/
├── Channels/EmailChannel.php          # Envio de email
├── Filament/
│   ├── Resources/
│   │   ├── TemplateResource.php       # CRUD de templates
│   │   └── NotificationResource.php   # Visualização de notificações
│   └── Widgets/
│       ├── StatsOverview.php          # Estatísticas do dashboard
│       └── NotificationsChart.php     # Gráfico de envios
├── Http/Controllers/Api/V1/
│   └── NotificationController.php     # API controller
├── Jobs/SendNotificationJob.php       # Job de envio assíncrono
├── Models/
│   ├── Template.php                   # Modelo de template
│   └── Notification.php               # Modelo de notificação
└── Services/TemplateRenderer.php      # Renderização de templates
```

## Templates de Exemplo

O seeder inclui 3 templates:
- `welcome` — Boas-vindas
- `payment_confirmed` — Confirmação de pagamento
- `password_reset` — Recuperação de password

## Roadmap

- [ ] Canal SMS (Africa's Talking, Plivo)
- [ ] Canal WhatsApp (Business API)
- [ ] Push Notifications (Firebase)
- [ ] Multi-tenancy
- [ ] Webhooks de status
- [ ] Envio em bulk
- [ ] Rate limiting na API
- [ ] SDKs (PHP, Node, Python)
- [ ] Testes automatizados (Pest)
- [ ] Docker Compose

## Licença

MIT License — Copyright 2026 anselmocossa
