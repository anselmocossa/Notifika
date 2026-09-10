<?php

namespace App\Support\Sms;

/**
 * Normaliza números de telefone para E.164.
 *
 * Nesta leva só Moçambique está implementado: números móveis 82–87, com 9
 * dígitos, aceites com ou sem +258 / 258 / 00258. Outros países ficam por
 * configuração — desligados por omissão (SDD §5) — e lançam
 * InvalidPhoneNumberException enquanto não houver um formato dedicado.
 */
final class PhoneNumber
{
    private function __construct(
        private readonly string $e164,
    ) {}

    public static function forMozambique(string $raw): self
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        foreach (['00258', '258'] as $prefix) {
            if (str_starts_with($digits, $prefix)) {
                $digits = substr($digits, strlen($prefix));
                break;
            }
        }

        if (! preg_match('/^8[2-7]\d{7}$/', $digits)) {
            throw new InvalidPhoneNumberException(
                "'{$raw}' não é um número de telemóvel moçambicano válido (82–87, 9 dígitos)."
            );
        }

        return new self('+258'.$digits);
    }

    /**
     * Ponto de entrada único usado pela validação da API. Só 'MZ' está
     * implementado; qualquer outro código de país é rejeitado por agora.
     */
    public static function parse(string $raw, string $country = 'MZ'): self
    {
        return match (strtoupper($country)) {
            'MZ' => self::forMozambique($raw),
            default => throw new InvalidPhoneNumberException(
                "País '{$country}' não suportado para SMS nesta instalação."
            ),
        };
    }

    public static function isValidForMozambique(string $raw): bool
    {
        try {
            self::forMozambique($raw);

            return true;
        } catch (InvalidPhoneNumberException) {
            return false;
        }
    }

    public function value(): string
    {
        return $this->e164;
    }

    public function __toString(): string
    {
        return $this->e164;
    }
}
