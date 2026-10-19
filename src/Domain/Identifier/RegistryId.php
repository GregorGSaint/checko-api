<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Identifier;

use CheckoApi\Domain\Exception\ValidationException;

final readonly class RegistryId
{
    private function __construct(
        public IdentifierKind $kind,
        public string $value,
    ) {}

    public static function forCompany(string $raw): self
    {
        $value = self::digits($raw, 'Идентификатор организации — ИНН из 10 цифр или ОГРН из 13 цифр');

        return match (strlen($value)) {
            10 => self::checked(IdentifierKind::Inn, $value),
            13 => self::checked(IdentifierKind::Ogrn, $value),
            12, 15 => throw ValidationException::wrongResource(sprintf(
                'Идентификатор из %d цифр принадлежит индивидуальному предпринимателю, используйте /v1/entrepreneurs/%s',
                strlen($value),
                $value,
            )),
            default => throw ValidationException::invalidIdentifier(
                'Идентификатор организации — ИНН из 10 цифр или ОГРН из 13 цифр',
            ),
        };
    }

    public static function forEntrepreneur(string $raw): self
    {
        $value = self::digits($raw, 'Идентификатор ИП — ИНН из 12 цифр или ОГРНИП из 15 цифр');

        return match (strlen($value)) {
            12 => self::checked(IdentifierKind::Inn, $value),
            15 => self::checked(IdentifierKind::Ogrnip, $value),
            10, 13 => throw ValidationException::wrongResource(sprintf(
                'Идентификатор из %d цифр принадлежит организации, используйте /v1/companies/%s',
                strlen($value),
                $value,
            )),
            default => throw ValidationException::invalidIdentifier(
                'Идентификатор ИП — ИНН из 12 цифр или ОГРНИП из 15 цифр',
            ),
        };
    }

    private static function digits(string $raw, string $message): string
    {
        $value = trim($raw);

        if (!ctype_digit($value)) {
            throw ValidationException::invalidIdentifier($message);
        }

        return $value;
    }

    private static function checked(IdentifierKind $kind, string $value): self
    {
        if (!$kind->isValid($value)) {
            throw ValidationException::invalidIdentifier(
                sprintf('%s %s не прошёл проверку контрольного числа', $kind->label(), $value),
            );
        }

        return new self($kind, $value);
    }
}
