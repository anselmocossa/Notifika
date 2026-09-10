<?php

namespace Tests\Unit\Support\Sms;

use App\Support\Sms\SmsEncoding;
use PHPUnit\Framework\TestCase;

class SmsEncodingTest extends TestCase
{
    public function test_plain_ascii_is_gsm7(): void
    {
        $result = SmsEncoding::analyze('Ola, o seu codigo e 1234.');

        $this->assertSame(SmsEncoding::GSM7, $result->encoding);
        $this->assertSame(1, $result->segments);
    }

    public function test_e_agudo_cabe_no_gsm7(): void
    {
        // O SDD é explícito: 'é', 'à' e 'Ç' maiúsculo cabem no alfabeto básico GSM-7.
        $result = SmsEncoding::analyze('Café pronto');

        $this->assertSame(SmsEncoding::GSM7, $result->encoding);
    }

    public function test_a_grave_cabe_no_gsm7(): void
    {
        // 'à' (acento grave) está no alfabeto básico; 'á' (agudo) NÃO está
        // — é um dos casos que força UCS-2 (ver teste abaixo).
        $result = SmsEncoding::analyze('Vamos à reuniao');

        $this->assertSame(SmsEncoding::GSM7, $result->encoding);
    }

    public function test_a_agudo_forca_ucs2(): void
    {
        $result = SmsEncoding::analyze('Está tudo bem');

        $this->assertSame(SmsEncoding::UCS2, $result->encoding);
    }

    public function test_c_maiusculo_cedilha_cabe_no_gsm7(): void
    {
        // Ç maiúsculo está no alfabeto básico; ç minúsculo não está.
        $result = SmsEncoding::analyze('CODIGO: Ç');

        $this->assertSame(SmsEncoding::GSM7, $result->encoding);
    }

    public function test_c_cedilha_minusculo_forca_ucs2(): void
    {
        $result = SmsEncoding::analyze('Notificação');

        $this->assertSame(SmsEncoding::UCS2, $result->encoding);
    }

    public function test_a_til_forca_ucs2(): void
    {
        $result = SmsEncoding::analyze('Configuração');

        $this->assertSame(SmsEncoding::UCS2, $result->encoding);
    }

    public function test_160_gsm7_characters_is_a_single_segment(): void
    {
        $text = str_repeat('a', 160);

        $result = SmsEncoding::analyze($text);

        $this->assertSame(SmsEncoding::GSM7, $result->encoding);
        $this->assertSame(160, $result->length);
        $this->assertSame(1, $result->segments);
    }

    public function test_161_gsm7_characters_needs_two_segments(): void
    {
        $text = str_repeat('a', 161);

        $result = SmsEncoding::analyze($text);

        $this->assertSame(SmsEncoding::GSM7, $result->encoding);
        $this->assertSame(161, $result->length);
        $this->assertSame(2, $result->segments);
    }

    public function test_extension_table_characters_count_as_two(): void
    {
        // '^' pertence à tabela de extensão do GSM-7: escape + código = 2.
        $result = SmsEncoding::analyze('^');

        $this->assertSame(SmsEncoding::GSM7, $result->encoding);
        $this->assertSame(2, $result->length);
    }

    public function test_euro_sign_counts_as_two(): void
    {
        $result = SmsEncoding::analyze('Custo: 5€');

        $this->assertSame(SmsEncoding::GSM7, $result->encoding);
        // "Custo: 5" = 8 chars (1 cada) + '€' (2) = 10.
        $this->assertSame(10, $result->length);
    }

    public function test_70_ucs2_characters_is_a_single_segment(): void
    {
        $text = str_repeat('ç', 70);

        $result = SmsEncoding::analyze($text);

        $this->assertSame(SmsEncoding::UCS2, $result->encoding);
        $this->assertSame(70, $result->length);
        $this->assertSame(1, $result->segments);
    }

    public function test_71_ucs2_characters_needs_two_segments(): void
    {
        $text = str_repeat('ç', 71);

        $result = SmsEncoding::analyze($text);

        $this->assertSame(SmsEncoding::UCS2, $result->encoding);
        $this->assertSame(2, $result->segments);
    }

    public function test_empty_body_has_zero_segments(): void
    {
        $result = SmsEncoding::analyze('');

        $this->assertSame(0, $result->segments);
    }
}
