<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Search;

use CheckoApi\Domain\Exception\ValidationException;
use DateTimeImmutable;

enum SearchBy: string
{
    case Name = 'name';
    case FounderName = 'founder-name';
    case LeaderName = 'leader-name';
    case Okved = 'okved';
    case RegistrationDate = 'reg-date';
    case UpdateDate = 'upd-date';
    case Phone = 'phone';
    case Email = 'email';

    private const MIN_TEXT_LENGTH = 4;

    public function requiresOrganizations(): bool
    {
        return in_array($this, [self::FounderName, self::LeaderName, self::Phone, self::Email], true);
    }

    public function normalize(string $query): string
    {
        $query = trim($query);

        if ($query === '') {
            throw ValidationException::invalidParameter('query', 'Параметр query обязателен');
        }

        return match ($this) {
            self::Name, self::FounderName, self::LeaderName => self::text($query),
            self::Okved => self::okved($query),
            self::RegistrationDate, self::UpdateDate => self::date($query),
            self::Phone => self::phone($query),
            self::Email => self::email($query),
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn(self $case): string => $case->value, self::cases());
    }

    private static function text(string $query): string
    {
        if (mb_strlen($query) < self::MIN_TEXT_LENGTH) {
            throw ValidationException::invalidParameter(
                'query',
                sprintf('Для поиска по наименованию или ФИО нужно не меньше %d символов', self::MIN_TEXT_LENGTH),
            );
        }

        return $query;
    }

    private static function okved(string $query): string
    {
        if (preg_match('/^\d{2}(\.\d{1,2}){0,2}$/', $query) !== 1) {
            throw ValidationException::invalidParameter('query', 'Код ОКВЭД ожидается в формате 62, 62.01 или 62.01.1');
        }

        return $query;
    }

    private static function date(string $query): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $query);

        if ($date === false || $date->format('Y-m-d') !== $query) {
            throw ValidationException::invalidParameter('query', 'Дата ожидается в формате ГГГГ-ММ-ДД');
        }

        return $query;
    }

    private static function phone(string $query): string
    {
        $phone = (string) preg_replace('/[\s()\-]/', '', $query);

        if (preg_match('/^(\+7|8)\d{10}$/', $phone) !== 1) {
            throw ValidationException::invalidParameter('query', 'Телефон ожидается в формате +7XXXXXXXXXX или 8XXXXXXXXXX');
        }

        return $phone;
    }

    private static function email(string $query): string
    {
        if (filter_var($query, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::invalidParameter('query', 'Некорректный адрес электронной почты');
        }

        return $query;
    }
}
