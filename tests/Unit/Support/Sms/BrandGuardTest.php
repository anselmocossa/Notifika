<?php

namespace Tests\Unit\Support\Sms;

use App\Support\Sms\BrandGuard;
use App\Support\Sms\SmsEncoding;
use PHPUnit\Framework\TestCase;

class BrandGuardTest extends TestCase
{
    public function test_prefixes_the_body_when_brand_is_missing(): void
    {
        $result = BrandGuard::apply('A sua prestação vence amanhã.', 'Notifika');

        $this->assertSame('Notifika: A sua prestação vence amanhã.', $result);
    }

    public function test_does_not_prefix_when_brand_is_already_present(): void
    {
        $body = 'Notifika: o seu código é 1234.';

        $result = BrandGuard::apply($body, 'Notifika');

        $this->assertSame($body, $result);
    }

    public function test_does_not_prefix_when_brand_is_already_present_in_lowercase(): void
    {
        $body = 'A notifika lembra: a sua prestação vence amanhã.';

        $result = BrandGuard::apply($body, 'Notifika');

        $this->assertSame($body, $result);
    }

    public function test_does_not_prefix_when_brand_is_already_present_in_uppercase(): void
    {
        $body = 'NOTIFIKA lembra: a sua prestação vence amanhã.';

        $result = BrandGuard::apply($body, 'Notifika');

        $this->assertSame($body, $result);
    }

    public function test_brand_check_is_case_insensitive(): void
    {
        $body = 'notifika confirma o seu pagamento.';

        $result = BrandGuard::apply($body, 'NOTIFIKA');

        $this->assertSame($body, $result);
    }

    public function test_empty_brand_name_leaves_body_unchanged(): void
    {
        $body = 'Mensagem sem marca definida.';

        $result = BrandGuard::apply($body, '');

        $this->assertSame($body, $result);
    }

    public function test_segments_returned_already_count_the_prefix(): void
    {
        $body = str_repeat('a', 155);
        $prefixed = BrandGuard::apply($body, 'Notifika');

        $encoding = SmsEncoding::analyze($prefixed);

        // "Notifika: " (10) + 155 = 165 > 160 -> 2 segmentos.
        $this->assertSame(2, $encoding->segments);
    }

    public function test_prefix_can_push_a_ucs2_body_from_one_to_two_segments(): void
    {
        // Corpo em UCS-2 (o "ç" força a codificação) com 65 caracteres: cabe
        // num único segmento (limite 70), mas não depois do prefixo somar 10.
        $body = str_repeat('a', 64).'ç';
        $this->assertSame(65, mb_strlen($body));

        $withoutPrefix = SmsEncoding::analyze($body);
        $this->assertSame(SmsEncoding::UCS2, $withoutPrefix->encoding);
        $this->assertSame(1, $withoutPrefix->segments);

        $prefixed = BrandGuard::apply($body, 'Notifika');
        $withPrefix = SmsEncoding::analyze($prefixed);

        // 65 + "Notifika: " (10) = 75 > 67 (limite multiparte UCS-2) -> 2 segmentos.
        $this->assertSame(2, $withPrefix->segments);
    }
}
