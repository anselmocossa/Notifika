<?php

namespace Tests\Unit\Support\Sms;

use App\Support\Sms\BrandGuard;
use App\Support\Sms\SmsEncoding;
use PHPUnit\Framework\TestCase;

class BrandGuardTest extends TestCase
{
    public function test_prefixes_the_body_when_brand_is_missing(): void
    {
        $result = BrandGuard::apply('O seu código é 1234.', 'Notifika');

        $this->assertSame("Marca: Notifika\nO seu código é 1234.", $result);
    }

    public function test_does_not_prefix_when_brand_is_already_present(): void
    {
        $body = 'Notifika: o seu código é 1234.';

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

        // "Marca: Notifika\n" (16) + 155 = 171 > 160 -> 2 segmentos.
        $this->assertSame(2, $encoding->segments);
    }
}
