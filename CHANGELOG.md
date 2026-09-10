# Changelog

Todas as alterações notáveis do **Notifika** são documentadas neste ficheiro.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o projecto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não publicado]

### Adicionado

- **Clientes.** Tabela `clients` (com `brand_name`) e `client_channel_credentials`
  (credenciais cifradas, `encrypted:array`). Os tokens Sanctum passam a pertencer
  **só** ao `Client` (`HasApiTokens`) — o `User` deixou de ter esse trait. A migração
  `2026_09_10_100005_convert_personal_access_tokens_tokenable_id_to_string` transfere,
  de uma vez, os tokens antigos ligados a um `User` para o cliente por omissão, sem
  tocar no hash do token: uma integração já emitida continua a autenticar com o mesmo
  token. Uma instalação self-hosted já tem esse cliente a partir do seeder
  (`ClientSeeder`) — funciona do zero mesmo sem correr o seeder, porque
  `Client::default()` é auto-suficiente (`firstOrCreate`). `Client::fromAuthenticatable`
  simplificou: na API o autenticado é sempre um `Client`; um autenticável que não seja
  (por exemplo um `User` autenticado por sessão, via o guard `web` do Sanctum) dá 403
  em JSON, nunca 500. CA-01, CA-03, CA-16.
- **Canal SMS via Twilio.** `notifications` ganha `client_id`, `channel`, `driver`,
  `provider_message_id`, `encoding`, `segments`, `cost`, `cost_currency`; `templates`
  ganha `client_id` (nulo = global) e `channel`. `ChannelManager` resolve o driver por
  cliente → instalação → erro 422 "canal não configurado" (CA-04). `TwilioSmsDriver`
  fala HTTP directo com a API REST da Twilio (sem SDK), classifica os erros permanentes
  (número inválido, destinatário desinscrito, região bloqueada, limite diário da conta)
  dos transitórios (429, 5xx, timeout) e grava `provider_message_id` e `cost` quando o
  fornecedor os devolve (CA-06, CA-07). `MailDriver` é o `EmailChannel` de sempre atrás
  do novo contrato `ChannelDriver`; `LogDriver` não faz nenhuma chamada de rede e serve
  o desenvolvimento e os testes (CA-15).
- `PhoneNumber` normaliza números moçambicanos (82–87, 9 dígitos, com ou sem
  `+258`/`258`/`00258`) para E.164 (CA-02). `SmsEncoding` detecta GSM-7/UCS-2 pela
  tabela GSM 03.38 (incluindo a tabela de extensão, que conta 2 por carácter) e estima
  segmentos (160/153 em GSM-7, 70/67 em UCS-2) (CA-05). `BrandGuard` prefixa
  `{brand_name}: ` quando o corpo de um SMS não menciona a marca do cliente — os
  `segments` devolvidos já contam esse prefixo (CA-17).
- API v1: campo `channel` opcional (por omissão `email` — uma integração existente não
  nota diferença nenhuma, CA-01); o `to` é validado por canal; a resposta acrescenta
  `channel` e, no SMS, `encoding`/`segments`. `index`/`show` passam a filtrar sempre
  pelo cliente do token (CA-03) — antes qualquer token via as notificações de todos.
- Painel Filament: recurso **Clientes** (dados e credenciais por canal; os campos de
  segredo — Account SID, Auth Token — nunca mostram o valor guardado e só o substituem
  quando preenchidos de novo, CA-12); **Notificações** ganha filtros de cliente e canal,
  e mostra canal/segmentos/custo.
- Testes de fumo: dashboard do painel Filament (`DashboardTest`), páginas
  list/create/edit dos resources de Notificações e Templates
  (`NotificationResourceTest`, `TemplateResourceTest`), e a API v1 de
  notificações com Sanctum, incluindo rejeição sem token/com token
  inválido (`NotificationApiTest`).
- Testes do login do painel Filament com credenciais válidas e inválidas
  (`LoginTest`) e testes que comprovam que a API v1 continua acessível
  por token Bearer sem token CSRF, incluindo uma asserção estrutural de
  que a rota `POST /api/v1/notifications` não passa por nenhum
  middleware de protecção CSRF (`NotificationApiCsrfTest`).
- `phpunit.pgsql.xml` corre a suite inteira contra PostgreSQL (base
  `notifika_test`, isolada da base de desenvolvimento), sem mudar a
  omissão SQLite do `phpunit.xml` — ver secção "Testes" do README.
  `LegacyTokenCompatibilityApiTest` passa a recriar de propósito o
  esquema anterior à migração `2026_09_10_100005` (reverte-a, insere o
  token com `tokenable_id` inteiro, migra outra vez) para provar de
  ponta a ponta que um token emitido antes desta leva continua a
  autenticar, com o mesmo hash, depois do upgrade (CA-01).
  `NonClientAuthenticationApiTest` cobre o 403 em JSON de um autenticável
  que não seja `Client`.

### Alterado

- Filament actualizado de 3.x para **5.8.1** (via 4.x), incluindo a
  adaptação do código a `Schema`/`->components()` em vez de
  `Form`/`Infolist`/`->schema()`, aos novos tipos de `$navigationIcon`
  e `$navigationGroup`, e a `$heading`/`$pollingInterval` como
  propriedades de instância nos widgets.
- Livewire actualizado para **4.4.4** (dependência do Filament 5).
- Laravel actualizado de 12.51.0 para **12.69.2** (última estável do
  major 12 já usado pelo projecto).
- `laravel/tinker` actualizado de `^2.10` para `^3.0`.
- Pacotes de desenvolvimento (`laravel/pint`, `laravel/sail`,
  `laravel/pail`, `nunomaduro/collision`, `mockery/mockery`,
  `fakerphp/faker`, `laravel/sanctum`) actualizados às últimas versões
  compatíveis.
- Laravel actualizado de **12.69.2** para **13.31.0**. O PHP mínimo do
  `composer.json` subiu de `^8.2` para `^8.3` (requisito do
  `laravel/framework` 13.x); `phpunit/phpunit` actualizado de
  `^11.5.3` para `^12.0`. Filament 5.8.1, Livewire 4.4.4 e Sanctum
  4.3.3 já suportavam Laravel 13 e mantiveram-se sem alterações de
  versão.
- Middleware de protecção contra falsificação de pedidos renomeado
  pelo Laravel 13 de `VerifyCsrfToken` para `PreventRequestForgery`
  (com verificação adicional do cabeçalho `Sec-Fetch-Site`); as
  referências directas à classe antiga em
  `app/Providers/Filament/AdminPanelProvider.php` (middleware do
  painel) e `config/sanctum.php` (`validate_csrf_token`, actualmente
  sem efeito porque a API não usa o modo stateful do Sanctum) foram
  migradas para a nova classe, conforme recomendado pelo guia oficial
  de upgrade.
- `config/cache.php`: adicionada a opção `serializable_classes` (a
  `false`), endurecimento por omissão do Laravel 13 que impede a
  desserialização de objectos PHP vindos da cache (protecção contra
  ataques de "gadget chain" caso a `APP_KEY` seja comprometida).
- `config/session.php`: adicionada a opção `serialization`, mantida
  em `php` (o comportamento implícito até à v12.x) para não invalidar
  as sessões activas do painel nesta actualização — o esqueleto do
  Laravel 13 usa `json` por omissão em instalações novas.

### Corrigido

- **`Client::createToken()` rebentava em PostgreSQL** com `SQLSTATE[22P02]:
  invalid input syntax for type bigint`. A migração do Sanctum cria
  `personal_access_tokens.tokenable_id` como `bigint` (pensado para chaves
  incrementais), mas o `Client` usa UUID (`HasUuids`) — em Postgres, a base de
  produção, um cliente não conseguia ter token nenhum, o que deitava abaixo
  todo o modelo multi-cliente. A suite não apanhava isto porque corria só em
  SQLite `:memory:`, que não impõe tipos de coluna. Corrigido pela migração
  `2026_09_10_100005_convert_personal_access_tokens_tokenable_id_to_string`,
  que alarga `tokenable_id` para `string(36)` (cabe um UUID) e, na mesma
  passagem, transfere os tokens antigos de `User` para o cliente por omissão.
  Encontrado num E2E real contra PostgreSQL.
- Um pedido à API v1 sem token e sem `Accept: application/json` respondia
  500, porque o Laravel tentava redireccionar para a rota nomeada `login`
  — que não existe (o Filament regista `filament.admin.auth.login`).
  `bootstrap/app.php` passa a nunca redireccionar pedidos `api/*` e a
  forçar sempre uma resposta JSON nesse grupo, com ou sem esse cabeçalho:
  agora dá sempre `401` em JSON (CA-19).
- 3 CVEs de severidade baixa (CVE-2026-45304, CVE-2026-45305,
  CVE-2026-45133) no `symfony/yaml`, dependência transitiva de
  `laravel/sail`, resolvidas ao actualizar para v8.1.6.
- **`BrandGuard` escrevia a palavra literal "Marca:" no corpo do SMS.** O SDD (§5 e
  CA-17) dizia que o guard "acrescenta `Marca: `", mas "Marca" era um marcador para o
  nome da marca do cliente, não texto para enviar tal e qual — um SMS com
  `brand_name = "Notifika"` e corpo `A sua prestação vence amanhã.` saía como `Marca:
  Notifika\nA sua prestação vence amanhã.` (com quebra de linha), em vez de `Notifika: A
  sua prestação vence amanhã.`. Corrigido em `BrandGuard::apply()`: o prefixo passa a
  ser sempre `{brand_name}: `, na mesma linha, sem a palavra "Marca". Encontrado num
  E2E real.

### Removido
