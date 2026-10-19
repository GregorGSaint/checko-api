<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Domain;

use CheckoApi\Domain\Identifier\Checksum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ChecksumTest extends TestCase
{
    #[DataProvider('innCases')]
    public function testInn(string $inn, bool $expected): void
    {
        self::assertSame($expected, Checksum::isValidInn($inn));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function innCases(): iterable
    {
        yield 'ПАО Сбербанк' => ['7707083893', true];
        yield 'ООО Яндекс' => ['7736207543', true];
        yield 'ИП Морозов' => ['402907671076', true];
        yield 'физлицо Греф' => ['770303580308', true];
        yield 'ошибка в последней цифре' => ['7707083894', false];
        yield 'ошибка в 11-й цифре ИНН ИП' => ['402907671066', false];
        yield 'три цифры' => ['123', false];
        yield 'буквы' => ['77070838A3', false];
        yield 'пустая строка' => ['', false];
    }

    #[DataProvider('ogrnCases')]
    public function testOgrn(string $ogrn, bool $expected): void
    {
        self::assertSame($expected, Checksum::isValidOgrn($ogrn));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function ogrnCases(): iterable
    {
        yield 'ПАО Сбербанк' => ['1027700132195', true];
        yield 'ООО Яндекс' => ['1027700229193', true];
        yield 'неверное контрольное число' => ['1027700132196', false];
        yield 'длина ОГРНИП' => ['310402910200035', false];
    }

    #[DataProvider('ogrnipCases')]
    public function testOgrnip(string $ogrnip, bool $expected): void
    {
        self::assertSame($expected, Checksum::isValidOgrnip($ogrnip));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function ogrnipCases(): iterable
    {
        yield 'ИП Морозов' => ['310402910200035', true];
        yield 'неверное контрольное число' => ['310402910200036', false];
        yield 'длина ОГРН' => ['1027700132195', false];
    }
}
