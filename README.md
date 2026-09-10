# Notifika

Plataforma de Notificações Multicanal Open Source para Moçambique e mercados emergentes.

**Stack:** Laravel 13.31 + Filament 5.8 + Livewire 4.4 + PostgreSQL + Redis + Twilio (SMS)

## Funcionalidades (MVP)

- **API REST** para envio de notificações por **e-mail** (SMTP/SendGrid) e **SMS** (Twilio)
- **Clientes:** o Notifika é open source self-hosted e serviço comercial da TED — cada
  cliente tem credenciais, tokens e consumo próprios. Uma instalação self-hosted tem um
  cliente por omissão, criado pelo seeder, e funciona só com credenciais no `.env`.
- **Sistema de Templates** com variáveis dinâmicas (`{{ name }}`, `{{ amount }}`, etc.)
- **Fila de mensagens** com Redis, retries e backoff exponencial — só os erros
  transitórios voltam a tentar-se; erros permanentes (número inválido, destinatário
  desinscrito, quota) falham de imediato
- **Dashboard Filament** com estatísticas em tempo real, gestão de clientes, credenciais,
  templates e notificações (com filtros por cliente e canal)
- **Autenticação API** via Laravel Sanctum (tokens ligados ao cliente)

## Requisitos

- PHP 8.3+
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

# 6. Executar migrações e seeders (cria o cliente por omissão — CA-16)
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

Os tokens pertencem a um `Client` (não a um `User`) — é o que liga cada notificação
ao seu cliente (CA-03). Numa instalação self-hosted, o `ClientSeeder` já criou o
cliente por omissão:

```bash
php artisan tinker
> $client = \App\Models\Client::default();
> echo $client->createToken('notifika-api')->plainTextToken;
```

Tokens antigos, criados antes desta funcionalidade (ligados a um `User`), continuam
a autenticar normalmente e ficam automaticamente atribuídos ao cliente por omissão —
nenhuma integração existente precisa de gerar um token novo.

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

**Enviar SMS** (campo `channel`, por omissão `email` — uma integração que nunca o
enviar continua a funcionar exactamente como antes):
```bash
curl -X POST http://localhost:8000/api/v1/notifications \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "channel": "sms",
    "to": "841234567",
    "content": "O seu código é 1234."
  }'
```

O `to` de um SMS aceita um número moçambicano (82–87, 9 dígitos), com ou sem
`+258` / `258` / `00258`, e é guardado em E.164. A resposta acrescenta `channel`
e, no SMS, `encoding` (`gsm7` ou `ucs2`) e `segments`:
```json
{
  "id": "ntf_ab12cd34ef56",
  "status": "queued",
  "channel": "sms",
  "encoding": "gsm7",
  "segments": 1,
  "created_at": "2026-09-10T12:00:00+00:00"
}
```

## Canal SMS (Twilio)

O driver Twilio fala HTTP directo com a API REST da Twilio — sem SDK, para manter o
projecto leve e os testes sem nenhuma chamada real (`Http::fake()`).

**Configuração da instalação** (usada quando o cliente não tem credenciais próprias —
ver "Clientes" no painel):
```bash
NOTIFIKA_SMS_DRIVER=twilio
NOTIFIKA_SMS_SENDER=NOTIFIKA        # sender ID alfanumérico
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

- **Sender ID.** Use sempre um **sender ID alfanumérico** (ex.: `NOTIFIKA`), registado
  na consola da Twilio — demora cerca de uma semana. **Um número internacional simples
  não entrega SMS na Movitel** (perto de metade do mercado moçambicano); o driver
  regista um aviso no log quando detecta esse caso.
- **Codificação.** Um SMS em português quase sempre tem `ã`, `ç`, `õ`, `á`, `í`, `ó`,
  `ú`, `â`, `ê` ou `ô` — qualquer um destes obriga a mensagem inteira a UCS-2 (70
  caracteres por mensagem única, 67 por segmento, em vez dos 160/153 do GSM-7). O `é`,
  o `à` e o `Ç` maiúsculo já fazem parte do alfabeto básico GSM-7.
- **Marca no corpo.** Um SMS para Moçambique sem o nome da marca no corpo pode ser
  bloqueado pela operadora — se o corpo não mencionar o `brand_name` do cliente, o
  Notifika prefixa `{brand_name}: ` automaticamente, na mesma linha (ex.: `Notifika: A
  sua prestação vence amanhã.`), e os `segments` devolvidos já contam esse prefixo.
- **Erros permanentes** (não voltam a tentar-se): número inválido, destinatário
  desinscrito (respondeu STOP), região bloqueada nas Geo Permissions da conta, limite
  diário de mensagens excedido. **Erros transitórios** (voltam a tentar-se com
  backoff): limite de débito da API (429), erros 5xx da Twilio, falhas de ligação/
  timeout.
- Sem `NOTIFIKA_SMS_DRIVER` configurado e sem credenciais de cliente, um pedido
  `channel: sms` devolve `422` ("canal não configurado").

## API Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/v1/notifications` | Enviar notificação (campo `channel`: `email` por omissão, ou `sms`) |
| GET | `/api/v1/notifications` | Listar notificações do cliente do token |
| GET | `/api/v1/notifications/{id}` | Ver status de notificação do cliente do token |

## Testes

Por omissão (`phpunit.xml`), a suite corre em SQLite `:memory:` — rápida, sem
preparação. Mas o SQLite não impõe tipos de coluna, por isso um defeito de
esquema (por exemplo, guardar um UUID numa coluna `bigint`) pode passar sem
ser detectado. **A base de produção é PostgreSQL** — por isso a suite corre
também aí, num ficheiro de configuração à parte que não muda a omissão SQLite:

```bash
# 1. Criar a base de testes UMA VEZ (isolada da base de desenvolvimento
#    "notifika" — nunca correr os testes contra ela)
createdb -h 127.0.0.1 -U anselmokossa notifika_test

# 2. Correr a suite contra PostgreSQL
php artisan test -c phpunit.pgsql.xml
# ou
vendor/bin/phpunit -c phpunit.pgsql.xml

# Suite em SQLite (omissão, sem preparação)
php artisan test
```

`phpunit.pgsql.xml` aponta para `DB_DATABASE=notifika_test` em
`127.0.0.1:5432`. Corre a suite completa nas duas bases antes de mesclar
qualquer alteração a `personal_access_tokens`, a `clients` ou a outra coluna
UUID.

## Estrutura do Projeto

```
app/
├── Channels/
│   ├── ChannelManager.php             # Resolve o driver: cliente -> instalação -> 422
│   ├── SendResult.php                 # sent / failed_permanent / failed_transient
│   ├── Contracts/ChannelDriver.php    # Contrato dos drivers
│   └── Drivers/
│       ├── MailDriver.php             # Canal email (mailer da instalação)
│       ├── LogDriver.php              # Não envia — regista no log (testes/dev)
│       └── TwilioSmsDriver.php        # SMS por HTTP directo à Twilio
├── Support/Sms/
│   ├── PhoneNumber.php                # Normaliza números MZ para E.164
│   ├── SmsEncoding.php                # GSM-7/UCS-2 e contagem de segmentos
│   ├── BrandGuard.php                 # Prefixa "{brand_name}: " quando falta a marca
│   └── SenderId.php                   # Detecta um sender ID que parece um número
├── Filament/
│   ├── Resources/
│   │   ├── ClientResource.php         # Clientes: dados e credenciais por canal
│   │   ├── TemplateResource.php       # CRUD de templates
│   │   └── NotificationResource.php   # Notificações, com filtros de cliente/canal
│   └── Widgets/
│       ├── StatsOverview.php          # Estatísticas do dashboard
│       └── NotificationsChart.php     # Gráfico de envios
├── Http/Controllers/Api/V1/
│   └── NotificationController.php     # API controller
├── Jobs/SendNotificationJob.php       # Job de envio assíncrono
├── Models/
│   ├── Client.php                     # Cliente (dono dos tokens Sanctum)
│   ├── ClientChannelCredential.php    # Credenciais cifradas por cliente/canal
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

- [x] Clientes, tokens por cliente e credenciais por canal
- [x] Canal SMS (Twilio)
- [ ] Driver de agregador SMS local (BudgetSMS, D7, EasySendSMS — a validar,
      sobretudo a entrega na Movitel)
- [ ] Canal WhatsApp (Meta Cloud API e Twilio, por cliente)
- [ ] Webhooks de estado (Twilio e Meta) e estados `delivered`/`read`
- [ ] Orçamento mensal por cliente (bloqueio e alerta aos 90%)
- [ ] Push Notifications (Firebase)
- [ ] Envio em bulk
- [ ] Rate limiting na API
- [ ] SDKs (PHP, Node, Python)
- [ ] Docker Compose

## Licença

MIT License — Copyright 2026 anselmocossa
