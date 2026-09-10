# Changelog

Todas as alterações notáveis do **Notifika** são documentadas neste ficheiro.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o projecto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não publicado]

### Adicionado

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

- 3 CVEs de severidade baixa (CVE-2026-45304, CVE-2026-45305,
  CVE-2026-45133) no `symfony/yaml`, dependência transitiva de
  `laravel/sail`, resolvidas ao actualizar para v8.1.6.

### Removido
