<?php

namespace Tests\Unit\Support\Sms;

use App\Support\Sms\InvalidPhoneNumberException;
use App\Support\Sms\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('validMozambiqueNumbers')]
    public function test_valid_mozambique_numbers_normalize_to_e164(string $raw, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::forMozambique($raw)->value());
    }

    public static function validMozambiqueNumbers(): array
    {
        return [
            'sem prefixo, 84' => ['841234567', '+258841234567'],
            'sem prefixo, 82' => ['821234567', '+258821234567'],
            'sem prefixo, 87' => ['871234567', '+258871234567'],
            'com +258' => ['+258841234567', '+258841234567'],
            'com 258' => ['258841234567', '+258841234567'],
            'com 00258' => ['00258841234567', '+258841234567'],
            'com espaços e traços' => ['+258 84-123 4567', '+258841234567'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers_are_rejected(string $raw): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneNumber::forMozambique($raw);
    }

    public static function invalidNumbers(): array
    {
        return [
            'prefixo de rede inexistente (88)' => ['881234567'],
            'prefixo de rede inexistente (81)' => ['811234567'],
            'curto demais' => ['8412345'],
            'longo demais' => ['84123456789'],
            'não numérico' => ['84abcde67'],
            'vazio' => [''],
            'fixo (21...)' => ['21301234'],
        ];
    }

    public function test_is_valid_for_mozambique_does_not_throw(): void
    {
        $this->assertTrue(PhoneNumber::isValidForMozambique('841234567'));
        $this->assertFalse(PhoneNumber::isValidForMozambique('881234567'));
    }

    public function test_parse_rejects_unsupported_countries(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneNumber::parse('841234567', 'ZA');
    }

    public function test_parse_defaults_to_mozambique(): void
    {
        $this->assertSame('+258841234567', PhoneNumber::parse('841234567')->value());
    }

    public function test_string_cast_returns_e164(): void
    {
        $phone = PhoneNumber::forMozambique('841234567');

        $this->assertSame('+258841234567', (string) $phone);
    }
}
