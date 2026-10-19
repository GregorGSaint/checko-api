<?php

declare(strict_types=1);

namespace CheckoApi\Bootstrap;

final class DotEnv
{
    /**
     * @return array<string, string>
     */
    public static function parse(string $path): array
    {
        if (!is_readable($path)) {
            return [];
        }

        $values = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $values[trim($name)] = trim(trim($value), '"\'');
        }

        return $values;
    }
}
