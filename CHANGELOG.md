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

### Corrigido

- 3 CVEs de severidade baixa (CVE-2026-45304, CVE-2026-45305,
  CVE-2026-45133) no `symfony/yaml`, dependência transitiva de
  `laravel/sail`, resolvidas ao actualizar para v8.1.6.

### Removido
