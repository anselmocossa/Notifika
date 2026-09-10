<?php

namespace App\Support\Sms;

/**
 * A Twilio recomenda que um SMS enviado para Moçambique contenha o nome da
 * marca no corpo — sem ele, a mensagem pode ser bloqueada (SDD §2, CA-17).
 * Se o corpo não mencionar o brand_name do cliente (comparação sem
 * distinguir maiúsculas de minúsculas), prefixa "{brand_name}: " no
 * início, na mesma linha — nunca a palavra literal "Marca".
 */
final class BrandGuard
{
    public static function apply(string $body, string $brandName): string
    {
        $brandName = trim($brandName);

        if ($brandName === '' || self::mentionsBrand($body, $brandName)) {
            return $body;
        }

        return "{$brandName}: {$body}";
    }

    public static function mentionsBrand(string $body, string $brandName): bool
    {
        return mb_stripos($body, $brandName) !== false;
    }
}
