<?php

declare(strict_types=1);

namespace CheckoApi\Persistence;

use DateTimeImmutable;
use DateTimeZone;

final class Timestamp
{
    private const FORMAT = 'Y-m-d H:i:s';

    public static function format(DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new DateTimeZone('UTC'))->format(self::FORMAT);
    }

    public static function parse(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
