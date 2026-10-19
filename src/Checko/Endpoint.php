<?php

declare(strict_types=1);

namespace CheckoApi\Checko;

enum Endpoint: string
{
    case Company = 'company';
    case Entrepreneur = 'entrepreneur';
    case Finances = 'finances';
    case Search = 'search';

    /**
     * @param array<mixed> $body
     */
    public function isNotFound(array $body): bool
    {
        return match ($this) {
            self::Company, self::Entrepreneur => empty($body['data']),
            self::Finances => empty($body['company']),
            self::Search => false,
        };
    }

    public function cacheTtl(): int
    {
        return match ($this) {
            self::Company, self::Entrepreneur => 86_400,
            self::Finances => 30 * 86_400,
            self::Search => 3_600,
        };
    }
}
