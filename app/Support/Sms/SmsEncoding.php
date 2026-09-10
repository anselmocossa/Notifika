<?php

namespace App\Support\Sms;

/**
 * Detecta GSM-7 vs UCS-2 (alfabeto GSM 03.38) e conta segmentos.
 *
 * - GSM-7: mensagem única até 160 caracteres; concatenada, 153 por segmento.
 * - UCS-2: mensagem única até 70 caracteres; concatenada, 67 por segmento.
 *
 * A tabela de extensão do GSM-7 (^ { } \ [ ~ ] | €) exige um carácter de
 * escape antes do código — cada um destes conta 2 no comprimento GSM-7.
 *
 * Basta um carácter fora dos dois alfabetos (ç, ã, õ, á, í, ó, ú, â, ê, ô,
 * … em português) para a mensagem inteira passar a UCS-2. O é, o à e o Ç
 * maiúsculo já fazem parte do alfabeto básico GSM-7 (SDD §2).
 */
final class SmsEncoding
{
    public const GSM7 = 'gsm7';

    public const UCS2 = 'ucs2';

    public const GSM7_SINGLE_LIMIT = 160;

    public const GSM7_MULTIPART_LIMIT = 153;

    public const UCS2_SINGLE_LIMIT = 70;

    public const UCS2_MULTIPART_LIMIT = 67;

    /**
     * Alfabeto básico do GSM 03.38 (127 pontos de código, sem o ESC 0x1B).
     *
     * Construído por concatenação (em vez de uma única string com aspas
     * duplas) para não misturar '$' com caracteres multibyte: o PHP aceita
     * bytes >= 0x80 em nomes de variável, e isso tornaria a interpolação
     * ambígua dentro de uma string com aspas duplas. Com aspas simples não
     * há interpolação — por isso \n e \r entram à parte, concatenados.
     */
    private const GSM7_BASIC =
        '@£$¥èéùìòÇ'
        ."\n"
        .'Øø'
        ."\r"
        .'ÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';

    /**
     * Tabela de extensão do GSM 03.38 — cada carácter aqui custa 2 (escape + código).
     */
    private const GSM7_EXTENDED = '^{}\[~]|€';

    private function __construct(
        public readonly string $encoding,
        public readonly int $length,
        public readonly int $segments,
    ) {}

    public static function analyze(string $text): self
    {
        if (self::isGsm7Compatible($text)) {
            $length = self::gsm7Length($text);

            return new self(self::GSM7, $length, self::segmentsFor($length, self::GSM7_SINGLE_LIMIT, self::GSM7_MULTIPART_LIMIT));
        }

        $length = mb_strlen($text);

        return new self(self::UCS2, $length, self::segmentsFor($length, self::UCS2_SINGLE_LIMIT, self::UCS2_MULTIPART_LIMIT));
    }

    private static function segmentsFor(int $length, int $singleLimit, int $multipartLimit): int
    {
        if ($length === 0) {
            return 0;
        }

        return $length <= $singleLimit ? 1 : (int) ceil($length / $multipartLimit);
    }

    public static function isGsm7Compatible(string $text): bool
    {
        $chars = mb_str_split($text);

        foreach ($chars as $char) {
            if (! str_contains(self::GSM7_BASIC, $char) && ! str_contains(self::GSM7_EXTENDED, $char)) {
                return false;
            }
        }

        return true;
    }

    public static function gsm7Length(string $text): int
    {
        $length = 0;

        foreach (mb_str_split($text) as $char) {
            $length += str_contains(self::GSM7_EXTENDED, $char) ? 2 : 1;
        }

        return $length;
    }
}
