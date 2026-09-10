# SDD — Canais SMS e WhatsApp, com clientes

**Projecto:** Notifika · **Estado:** aprovado pelo Boss para implementação · **Data:** 10/09/2026
**Base:** branch `upgrade/laravel-filament` — Laravel 13.31, Filament 5.8.1, Livewire 4.4.4,
PHP ≥ 8.3, 18 testes verdes. A implementação vive no branch `feat/canais-sms-whatsapp`.

---

## 1. Decisões do Boss (10/09/2026)

| # | Decisão |
|---|---|
| D1 | **SMS por drivers.** Twilio já; um driver de agregador local quando houver contrato. Troca-se por configuração. |
| D2 | **WhatsApp com dois drivers**: Meta Cloud API directa e Twilio, escolhidos por cliente. |
| D3 | **O Notifika é as duas coisas**: open source self-hosted, e serviço comercial da TED. Logo: clientes, com credenciais, consumo e custo próprios. |

### Decisões técnicas do Master (10/09/2026)

| # | Decisão |
|---|---|
| T1 | **Sem SDKs de fornecedores.** Twilio e Meta falam por HTTP directo, pelo cliente HTTP do Laravel: menos dependências num projecto open source, e testes com `Http::fake()` sem chamadas reais. A assinatura dos webhooks do Twilio (HMAC-SHA1) e da Meta (HMAC-SHA256) implementa-se à mão. |
| T2 | **Os webhooks ficam em `routes/api.php`.** Neste projecto o grupo `api` não tem verificação CSRF (confirmado no upgrade para o Laravel 13), por isso não é preciso isentar nada. A autenticidade prova-se pela assinatura do fornecedor. |
| T3 | **Na instância da TED:** WhatsApp pela Meta directa por omissão, e SMS pelo Twilio. O driver WhatsApp do Twilio fica disponível para clientes que o prefiram. |
| T4 | **Duas levas.** Leva 1 = F1, mais a correcção do erro 500 da API (§3). Leva 2 = F2 + F3. |

## 2. Factos em que o desenho assenta (verificados a 10/09/2026)

**SMS**
- Twilio → Moçambique: **$0,417 por SMS**, preço único para Vodacom, Movitel e Tmcel.
  Os $0,42 e $0,83 do LoanZone foram um e dois segmentos.
- O LoanZone **abandonou o Twilio a 26/05/2026 por causa dos limites da conta trial**
  (116 jobs falhados por dia com "Account exceeded daily messages limit"), não por falha
  técnica. Uma conta paga nunca foi experimentada.
- Agregadores com cobertura das três redes anunciam **a partir de €0,043** por SMS
  (BudgetSMS; também D7, EasySendSMS) — cerca de dez vezes menos. **Por validar.**
- A Movitel tem perto de metade do mercado. **A entrega numa rede Movitel nunca foi
  provada** (o teste Twilio foi para um 84; o SMS de verificação da Google falhou num 87).
- **Codificação:** o alfabeto GSM-7 dá 160 caracteres (153 por segmento em mensagens
  longas). Basta um `ç`, `ã`, `õ`, `á`, `í`, `ó`, `ú`, `â`, `ê` ou `ô` para passar a
  **UCS-2: 70 caracteres (67 por segmento)**. O `é`, o `à` e o `Ç` maiúsculo cabem em
  GSM-7. Em português, isto pode duplicar o custo.
- **Remetente — as regras do Twilio para Moçambique** (guia oficial do país):
  - *"International Longcode Sender ID is not supported for Mozambique Movitel"*: **a
    Movitel não aceita SMS vindos de um número internacional.** Isto explica o SMS da
    Google que falhou no 87.
  - O sender ID alfanumérico é suportado e o **registo é recomendado** (cerca de 1 semana).
    O não registado é aceite mas desaconselhado. **Na prática, sem sender ID registado,
    metade do mercado (Movitel) pode não receber.**
  - Não há SMS bidireccional, short codes nem números locais.
  - *"SMS sent to Mozambique should contain the brand name or application name in the
    message body"* — **sem o nome da marca no corpo, o SMS pode ser bloqueado.**

**WhatsApp** (página oficial da Meta)
- Cobrança **por mensagem** desde 1/7/2025, por categoria: marketing, utility,
  authentication. Moçambique está na tabela regional "Rest of Africa".
- **Mensagens de serviço e templates utility dentro da janela de 24 horas são gratuitos.**
  Não há mudança anunciada.
- Uma mensagem **iniciada pela empresa** tem de usar um **template aprovado**.
- **Twilio:** envia templates por `ContentSid` + `ContentVariables` (Content API). Os
  templates criados na consola antiga não são compatíveis. Os callbacks de estado são
  `queued`, `sent`, `delivered`, `read` e `failed`.

## 3. O que o código faz hoje

- **Só e-mail, por construção.** `SendNotificationJob` recebe o `EmailChannel` injectado;
  o `to` é validado como `email`; a tabela `notifications` não tem canal; os templates
  têm assunto.
- **Não há clientes.** Os tokens Sanctum são de `User`.
- **Os estados param em `sent`.** Não sabemos se a mensagem chegou nem se foi lida.
- ⚠️ **Fuga de dados latente:** `GET /api/v1/notifications` e `GET …/{id}` não filtram por
  dono — qualquer token vê as notificações de todos. Hoje há um só utilizador; com clientes
  seria uma fuga. Corrigido pelo CA-03.
- ⚠️ **Erro 500 em vez de 401:** um pedido à API sem token e sem `Accept: application/json`
  rebenta, porque o Laravel tenta redireccionar para a rota `login`, que não existe (o
  Filament regista `filament.admin.auth.login`). Encontrado no upgrade; corrigido pelo CA-19.
- Retries cegos: qualquer excepção volta a tentar 3 vezes. Foi exactamente o que encheu o
  LoanZone de jobs falhados.

## 4. Modelo de dados

**`clients`** (novo) — `id` uuid, `name`, `slug` único, **`brand_name`** (vai no corpo de
cada SMS, §2), `is_active`, `monthly_budget` decimal nullable, `budget_currency`, timestamps.
Numa instalação self-hosted existe **um cliente por omissão**, criado pelo seeder.

**`client_channel_credentials`** (novo) — `client_id`, `channel`, `driver`,
`credentials` **cifradas** (cast `encrypted:array`), `sender` (número, sender ID ou
`phone_number_id` da Meta), `is_active`. Único por (`client_id`, `channel`).

**`notifications`** (alterada) — mais: `client_id`, `channel` (`email` · `sms` ·
`whatsapp`), `driver`, `provider_message_id` (único por driver), `encoding`,
`segments`, `cost`, `cost_currency`, `delivered_at`, `read_at`.
Estados: `queued` → `processing` → `sent` → `delivered` → `read`, ou `failed`.

**`templates`** (alterada) — mais: `client_id` (nulo = global), `channel`,
`meta_template_name`, `meta_language`, `twilio_content_sid`. O `subject` só conta para
e-mail.

**`provider_events`** (novo) — registo bruto de cada webhook: `driver`, `payload` json,
`notification_id` nullable, `received_at`. Serve de auditoria e para repetir o
processamento.

**Tokens:** passam a pertencer ao **cliente** (`Client` usa `HasApiTokens`), para uma
integração não morrer quando a pessoa que a criou sai.

## 5. Arquitectura

```
API / Job
   │
   ▼
ChannelManager ──► resolve o driver:
   │                 1. credenciais do cliente para o canal
   │                 2. senão, as da instalação (config / .env)
   │                 3. senão, erro "canal não configurado"
   ▼
Driver  (contrato: send(Notification): SendResult)
   ├─ email:     MailDriver                  (o de hoje)
   ├─ sms:       TwilioSmsDriver · LogDriver · AgregadorLocalDriver [fornecedor por decidir]
   └─ whatsapp:  MetaCloudDriver · TwilioWhatsAppDriver · LogDriver
```

- **`SendResult`** distingue três desfechos: `sent`, `failed_permanent` e `failed_transient`.
  Só o transitório volta a tentar. São permanentes: número inválido, destinatário
  desinscrito, quota ou configuração, template rejeitado. É a lição do commit `023883f`
  do LoanZone.
- **`PhoneNumber`**: normaliza para E.164; em Moçambique aceita 82–87 com ou sem `+258`.
  Os outros países ficam configuráveis.
- **`SmsEncoding`**: detecta GSM-7 ou UCS-2, conta segmentos e estima o custo. Aparece na
  resposta da API e no painel, para o cliente ver o que um acento lhe custa.
- **`LogDriver`**: não chama ninguém e marca enviado. Serve o desenvolvimento, os testes
  e as demonstrações.
- **`BrandGuard`**: se o corpo de um SMS não tiver o `brand_name` do cliente, acrescenta
  `Marca: ` no início, porque em Moçambique uma mensagem sem marca pode ser bloqueada. O
  prefixo conta nos segmentos e no custo, e a API mostra-o.

## 6. API v1 — compatível com o que existe

`POST /api/v1/notifications`
- Campo novo **`channel`**, por omissão `email`: **as integrações que já existem não
  mudam nada.**
- O `to` é validado conforme o canal: e-mail, ou número MZ.
- **WhatsApp v1 só por template**, o único envio que a Meta permite fora da janela de 24h.
  O template do Notifika tem de ter o mapeamento para o driver; sem mapeamento → 422.
- A resposta acrescenta `channel` e, no SMS, `encoding` e `segments`.

`GET /api/v1/notifications[/{id}]` — **só as do cliente do token**, com `delivered_at` e
`read_at`.

**Webhooks** (sem Sanctum, mas **com assinatura verificada**):
- `POST /webhooks/twilio/status` — valida o `X-Twilio-Signature`.
- `GET /webhooks/meta` (verificação `hub.challenge`) e `POST /webhooks/meta` — valida o
  `X-Hub-Signature-256`.

## 7. Painel Filament

- **Clientes** (admin TED): dados, credenciais por canal (escrever sim, ler nunca), tokens,
  orçamento.
- **Notificações:** filtros por cliente, canal e estado; colunas de segmentos e custo.
- **Templates:** por canal, com os campos de mapeamento do WhatsApp.
- **Dashboard:** envios, entregas e custo por canal.

## 8. Fora de âmbito da v1

- **SMS recebidos:** o SMS bidireccional não é suportado em Moçambique — não há o que
  implementar.
- **Mensagens recebidas e conversas de WhatsApp.** Sem elas não se sabe se a janela de
  24h está aberta, por isso a v1 envia **só templates**. O texto livre dentro da janela
  fica para a v2.
- Gestão de opt-out ("STOP"). Na v1, o erro de desinscrito do fornecedor conta apenas
  como falha permanente.
- Campanhas e agendamento; voz, push e USSD.
- O driver local concreto. **O contrato fica pronto; a implementação espera pelo
  fornecedor.**

## 9. Critérios de aceitação

| # | Critério |
|---|---|
| CA-01 | Um pedido **sem `channel`** envia e-mail exactamente como hoje. |
| CA-02 | `channel=sms` com número MZ válido (82–87, com ou sem `+258`) é aceite e guardado em E.164; número inválido → 422 com mensagem clara. |
| CA-03 | Cada notificação pertence a um cliente. **Um token só vê e só envia pelo seu cliente.** |
| CA-04 | O driver resolve-se por: credenciais do cliente → instalação → erro 422 "canal não configurado". |
| CA-05 | SMS: a resposta traz `encoding` e `segments`; um texto com um só `ç` é UCS-2. |
| CA-06 | O envio Twilio grava `provider_message_id` e `driver`, e o `cost` quando o fornecedor o devolve. |
| CA-07 | Um erro permanente marca a falha **sem voltar a tentar**; um transitório volta a tentar com backoff. |
| CA-08 | WhatsApp: template sem mapeamento para o driver do cliente → 422. |
| CA-09 | Meta: envia pela Graph API com o `phone_number_id` do cliente. Twilio: `whatsapp:+258…` com `ContentSid` e `ContentVariables`. |
| CA-10 | Webhook Twilio: assinatura inválida → 403; válida → actualiza o estado e os timestamps. |
| CA-11 | Webhook Meta: responde ao `hub.challenge` com o verify token certo; assinatura inválida → 403; os `statuses` actualizam a notificação. |
| CA-12 | As credenciais ficam cifradas na base e **nunca** saem pela API nem em claro no painel depois de gravadas. |
| CA-13 | Orçamento mensal por cliente: excedido → os envios não críticos são recusados e registados; alerta aos 90%. |
| CA-14 | Todos os webhooks ficam em `provider_events`, mesmo os que não casam com nenhuma notificação. |
| CA-15 | **Nenhum teste faz chamadas HTTP reais** (`Http::fake` / cliente Twilio simulado / `LogDriver`). |
| CA-16 | Self-hosted sem painel de clientes: um cliente por omissão, com credenciais no `.env`, funciona do zero. |
| CA-17 | Um SMS cujo corpo não tenha o `brand_name` do cliente sai com o prefixo `Marca: `, e os segmentos devolvidos já o contam. |
| CA-19 | Um pedido à API sem token recebe **401 em JSON**, com ou sem `Accept: application/json` — nunca 500 nem um redireccionamento. |
| CA-18 | O driver Twilio SMS usa como remetente o **sender ID alfanumérico** do cliente. Se só houver um número internacional, avisa no painel e no log que a Movitel não entrega. |

## 10. Como se prova (resumo do TDD)

- **Unitários:** `PhoneNumber` (formatos MZ, rejeições); `SmsEncoding` (casos com `ç`, `ã`,
  `é`, 160 e 161 caracteres); a ordem de resolução do `ChannelManager`; cada driver com o
  HTTP simulado, incluindo erros permanentes e transitórios.
- **Feature:** a API por canal; a compatibilidade sem `channel`; o isolamento entre clientes
  (o token de A não vê B); os webhooks com assinatura válida e inválida; as transições de
  estado; o bloqueio por orçamento.
- **E2E no fim:** envio real pelo **número de teste gratuito da Meta Cloud API**. Um SMS
  real exige uma conta Twilio paga — decisão e custo do Boss.

## 11. O que depende do Boss

- **Twilio:** passar a conta a paga. A trial limita os envios diários e só entrega a números
  verificados.
- **Meta:** Business Manager verificado, uma app, um número dedicado (que não esteja já
  no WhatsApp) e os templates aprovados.
- **Agregador local:** escolher e contratar, depois de um **teste de entrega nas três
  redes, a Movitel primeiro**. Candidatos a avaliar: BudgetSMS, D7, EasySendSMS.
- **Sender ID — é o bloqueio número um do SMS.** Registar na Twilio um sender ID
  alfanumérico (por exemplo `NOTIFIKA`, ou o de cada cliente); demora cerca de uma semana.
  **Sem ele, a Movitel (metade do mercado) não recebe.** Pedir já, em paralelo com a F1.

## 12. Fases

| Fase | Conteúdo |
|---|---|
| F0 | Upgrade Laravel/Filament — **feito** (Laravel 13.31, Filament 5.8.1) |
| F1 | **Leva 1:** clientes, tokens por cliente, `channel`, `ChannelManager`, `LogDriver`, SMS Twilio, `BrandGuard`, correcção do CA-03 e do CA-19 |
| F2 | **Leva 2:** WhatsApp — Meta Cloud e Twilio — com mapeamento de templates |
| F3 | **Leva 2:** webhooks, estados `delivered` e `read`, custos, orçamento |
| F4 | Driver do agregador local, quando houver contrato |

## Fontes

- Preços oficiais do WhatsApp Business Platform — https://developers.facebook.com/docs/whatsapp/pricing
- Twilio SMS, Moçambique — https://www.twilio.com/en-us/sms/pricing/mz
- Regras de SMS do Twilio para Moçambique — https://www.twilio.com/en-us/guidelines/mz/sms
- Templates Twilio (Content API) — https://www.twilio.com/docs/content/send-templates-created-with-the-content-template-builder
- Recurso Message e callbacks Twilio — https://www.twilio.com/docs/messaging/api/message-resource
- BudgetSMS, Moçambique — https://www.budgetsms.net/sms-gateway-pricing/mz/mozambique/
- LoanZone, commits `023883f` e `1fc219e` (histórico de `app/Services/SmsService.php`)
