<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Identifier;

enum IdentifierKind: string
{
    case Inn = 'inn';
    case Ogrn = 'ogrn';
    case Ogrnip = 'ogrnip';

    public function label(): string
    {
        return match ($this) {
            self::Inn => 'ИНН',
            self::Ogrn => 'ОГРН',
            self::Ogrnip => 'ОГРНИП',
        };
    }

    public function isValid(string $value): bool
    {
        return match ($this) {
            self::Inn => Checksum::isValidInn($value),
            self::Ogrn => Checksum::isValidOgrn($value),
            self::Ogrnip => Checksum::isValidOgrnip($value),
        };
    }
}
