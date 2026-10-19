<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Support;

use CheckoApi\Support\Json;
use RuntimeException;

final class Fixture
{
    public static function directory(): string
    {
        return dirname(__DIR__, 2) . '/resources/fixtures/checko';
    }

    public static function read(string $name): string
    {
        $contents = file_get_contents(self::directory() . '/' . $name);

        if ($contents === false) {
            throw new RuntimeException('Фикстура не найдена: ' . $name);
        }

        return $contents;
    }

    /**
     * @return array<mixed>
     */
    public static function json(string $name): array
    {
        return Json::decodeArray(self::read($name));
    }
}
