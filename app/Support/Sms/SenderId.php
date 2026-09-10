<?php

namespace App\Support\Sms;

/**
 * O remetente Twilio para SMS em Moçambique deve ser um sender ID
 * alfanumérico (CA-18). O guia oficial da Twilio para Moçambique é claro:
 * "International Longcode Sender ID is not supported for Mozambique
 * Movitel" — um número internacional simples não entrega na Movitel
 * (perto de metade do mercado). Usado pelo TwilioSmsDriver (para o aviso
 * no log) e pelo painel Filament (para o aviso no formulário de credenciais).
 */
final class SenderId
{
    public static function looksLikeInternationalNumber(string $sender): bool
    {
        return preg_match('/^\+?\d+$/', trim($sender)) === 1;
    }
}
