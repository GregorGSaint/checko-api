<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use CheckoApi\Domain\Exception\ValidationException;

final readonly class PageRequest
{
    public const MAX_LIMIT = 100;

    public function __construct(
        public int $page,
        public int $limit,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public static function fromParameters(array $parameters, int $defaultLimit): self
    {
        return new self(
            self::positiveInteger($parameters, 'page', 1, PHP_INT_MAX),
            self::positiveInteger($parameters, 'limit', $defaultLimit, self::MAX_LIMIT),
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    private static function positiveInteger(array $parameters, string $name, int $default, int $max): int
    {
        if (!isset($parameters[$name])) {
            return $default;
        }

        $value = filter_var($parameters[$name], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $max]]);

        if ($value === false) {
            throw ValidationException::invalidParameter($name, sprintf('Параметр %s — целое число от 1 до %d', $name, $max));
        }

        return $value;
    }
}
