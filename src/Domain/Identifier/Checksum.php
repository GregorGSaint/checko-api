<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Identifier;

final class Checksum
{
    private const INN_10_WEIGHTS = [2, 4, 10, 3, 5, 9, 4, 6, 8];
    private const INN_12_FIRST_WEIGHTS = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
    private const INN_12_SECOND_WEIGHTS = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8];

    public static function isValidInn(string $value): bool
    {
        if (!ctype_digit($value)) {
            return false;
        }

        return match (strlen($value)) {
            10 => self::weightedDigit($value, self::INN_10_WEIGHTS) === (int) $value[9],
            12 => self::weightedDigit($value, self::INN_12_FIRST_WEIGHTS) === (int) $value[10]
                && self::weightedDigit($value, self::INN_12_SECOND_WEIGHTS) === (int) $value[11],
            default => false,
        };
    }

    public static function isValidOgrn(string $value): bool
    {
        return self::hasValidRemainderDigit($value, 13, 11);
    }

    public static function isValidOgrnip(string $value): bool
    {
        return self::hasValidRemainderDigit($value, 15, 13);
    }

    /**
     * @param list<int> $weights
     */
    private static function weightedDigit(string $value, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $position => $weight) {
            $sum += $weight * (int) $value[$position];
        }

        return $sum % 11 % 10;
    }

    private static function hasValidRemainderDigit(string $value, int $length, int $divisor): bool
    {
        if (strlen($value) !== $length || !ctype_digit($value)) {
            return false;
        }

        return (int) substr($value, 0, -1) % $divisor % 10 === (int) $value[$length - 1];
    }
}
