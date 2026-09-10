<?php

namespace App\Channels\Drivers;

use App\Channels\Contracts\ChannelDriver;
use App\Channels\ResolvedChannel;
use App\Channels\SendResult;
use App\Models\Notification;
use App\Support\Sms\SenderId;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS por Twilio, por HTTP directo — sem SDK (T1 do SDD): menos uma
 * dependência num projecto open source, e os testes usam Http::fake() sem
 * nenhuma chamada real (CA-15).
 *
 * POST https://api.twilio.com/2010-04-01/Accounts/{AccountSid}/Messages.json
 * com autenticação Basic (Account SID + Auth Token).
 *
 * ---------------------------------------------------------------------
 * Tabela de classificação de erros Twilio (confirmada na documentação
 * oficial em developers.twilio.com/docs/api/errors/{code}, 10/09/2026):
 *
 *   PERMANENTES (não voltam a tentar-se):
 *   21211 — Invalid 'To' Phone Number.
 *           Número de destino mal formatado ou inválido. Corrigir o
 *           número não é algo que um retry resolva.
 *   21408 — Permission to send an SMS has not been enabled for the
 *           region indicated by the 'To' number (Geo Permissions).
 *           Região/país bloqueado nas definições da conta — configuração.
 *   21610 — Message cannot be sent to the 'To' number because the
 *           customer has replied with STOP.
 *           Destinatário desinscrito; só volta a aceitar com "START".
 *   63038 — Account exceeded the number of daily messages sent.
 *           Limite diário da conta (quota). O limite reinicia às 24h —
 *           muito depois do último backoff do job (30s/120s/600s) —
 *           por isso repetir dentro desse intervalo nunca vai resultar.
 *           O LoanZone tratava este código como quota/configuração
 *           (abandonou o Twilio em 26/05/2026 por causa dele); aqui
 *           confirma-se e mantém-se esse tratamento.
 *
 *   TRANSITÓRIOS (voltam a tentar-se com backoff):
 *   20429 — Too Many Requests (rate limit da API REST da Twilio). A
 *           própria documentação diz "safe to retry after backing off".
 *           NOTA: o LoanZone tratava este código como quota/configuração
 *           junto com o 63038 — verificado aqui que essa junção estava
 *           errada: 20429 é um limite de débito (throughput), não de
 *           conta, e resolve-se em segundos, não em 24h. Corrigido.
 *   HTTP 429 — mesmo caso do 20429, ao nível do transporte.
 *   HTTP 5xx — erro do lado da Twilio.
 *   Erros de ligação/timeout (ConnectionException) — falha de rede.
 *
 *   Por omissão, qualquer outro erro 4xx da Twilio fora desta tabela é
 *   tratado como permanente (um pedido mal formado não se corrige
 *   sozinho); qualquer 5xx ou 429 não listado é transitório.
 * ---------------------------------------------------------------------
 */
class TwilioSmsDriver implements ChannelDriver
{
    private const BASE_URL = 'https://api.twilio.com/2010-04-01';

    /** @var array<int, string> */
    private const PERMANENT_ERROR_CODES = [
        21211 => "Número de destino ('To') inválido.",
        21408 => 'Região do destinatário não permitida (Geo Permissions da conta Twilio).',
        21610 => 'Destinatário desinscrito (respondeu STOP a uma mensagem anterior).',
        63038 => 'Limite diário de mensagens da conta Twilio excedido.',
    ];

    public function send(Notification $notification, ResolvedChannel $channel): SendResult
    {
        $accountSid = $channel->credentials['account_sid'] ?? null;
        $authToken = $channel->credentials['auth_token'] ?? null;

        if (! $accountSid || ! $authToken) {
            return SendResult::failedPermanent('Credenciais da Twilio em falta (account_sid / auth_token).');
        }

        if (! $channel->sender) {
            return SendResult::failedPermanent('Remetente (sender ID) da Twilio não configurado para SMS.');
        }

        $this->warnIfSenderIsInternational($channel->sender, $notification);

        try {
            $response = Http::asForm()
                ->withBasicAuth($accountSid, $authToken)
                ->post(self::BASE_URL."/Accounts/{$accountSid}/Messages.json", [
                    'To' => $notification->to,
                    'From' => $channel->sender,
                    'Body' => $notification->content,
                ]);
        } catch (ConnectionException $e) {
            return SendResult::failedTransient("Erro de ligação à Twilio: {$e->getMessage()}");
        }

        if ($response->successful()) {
            return $this->resultFromSuccess($response->json() ?? []);
        }

        return $this->resultFromError($response);
    }

    private function resultFromSuccess(array $body): SendResult
    {
        $cost = null;

        if (isset($body['price']) && $body['price'] !== null) {
            // A Twilio devolve o preço como débito negativo (ex.: "-0.00750").
            $cost = ltrim((string) $body['price'], '-');
        }

        return SendResult::sent(
            providerMessageId: $body['sid'] ?? null,
            cost: $cost,
            costCurrency: $body['price_unit'] ?? null,
            raw: $body,
        );
    }

    private function resultFromError(Response $response): SendResult
    {
        $body = $response->json() ?? [];
        $code = isset($body['code']) ? (int) $body['code'] : null;
        $message = $body['message'] ?? $response->body();

        if ($code !== null && array_key_exists($code, self::PERMANENT_ERROR_CODES)) {
            return SendResult::failedPermanent(
                self::PERMANENT_ERROR_CODES[$code]." (Twilio {$code}: {$message})",
                $body,
            );
        }

        if ($response->status() === 429 || $response->serverError()) {
            return SendResult::failedTransient("Twilio HTTP {$response->status()}: {$message}", $body);
        }

        return SendResult::failedPermanent("Twilio HTTP {$response->status()}: {$message}", $body);
    }

    private function warnIfSenderIsInternational(string $sender, Notification $notification): void
    {
        if (! SenderId::looksLikeInternationalNumber($sender)) {
            return;
        }

        Log::warning('notifika.twilio_sms.international_sender_id', [
            'notification_id' => $notification->id,
            'sender' => $sender,
            'message' => "O remetente '{$sender}' parece um número internacional simples. ".
                'Segundo o guia da Twilio para Moçambique, a Movitel não entrega SMS vindos de '.
                'um sender ID internacional — registe um sender ID alfanumérico.',
        ]);
    }
}
