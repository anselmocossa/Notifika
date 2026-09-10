<?php

namespace App\Channels;

/**
 * O desfecho de uma tentativa de envio (SDD §5). Só o transitório volta a
 * tentar — é a lição do commit 023883f do LoanZone: uma excepção genérica
 * relançada às cegas encheu a fila de jobs falhados por causa de erros que
 * nunca iriam resolver-se sozinhos (número inválido, quota, configuração).
 */
final class SendResult
{
    public const SENT = 'sent';

    public const FAILED_PERMANENT = 'failed_permanent';

    public const FAILED_TRANSIENT = 'failed_transient';

    private function __construct(
        public readonly string $status,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $cost = null,
        public readonly ?string $costCurrency = null,
        public readonly ?string $errorMessage = null,
        public readonly array $raw = [],
    ) {}

    public static function sent(
        ?string $providerMessageId = null,
        ?string $cost = null,
        ?string $costCurrency = null,
        array $raw = [],
    ): self {
        return new self(self::SENT, providerMessageId: $providerMessageId, cost: $cost, costCurrency: $costCurrency, raw: $raw);
    }

    public static function failedPermanent(string $errorMessage, array $raw = []): self
    {
        return new self(self::FAILED_PERMANENT, errorMessage: $errorMessage, raw: $raw);
    }

    public static function failedTransient(string $errorMessage, array $raw = []): self
    {
        return new self(self::FAILED_TRANSIENT, errorMessage: $errorMessage, raw: $raw);
    }

    public function isSent(): bool
    {
        return $this->status === self::SENT;
    }

    public function isPermanentFailure(): bool
    {
        return $this->status === self::FAILED_PERMANENT;
    }

    public function isTransientFailure(): bool
    {
        return $this->status === self::FAILED_TRANSIENT;
    }
}
