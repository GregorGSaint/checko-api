<?php

declare(strict_types=1);

namespace CheckoApi\Support;

use JsonException;

final class Json
{
    private const ENCODE_FLAGS = JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_PRESERVE_ZERO_FRACTION;

    public static function encode(mixed $value, bool $pretty = false): string
    {
        return json_encode($value, self::ENCODE_FLAGS | ($pretty ? JSON_PRETTY_PRINT : 0));
    }

    /**
     * @return array<mixed>
     */
    public static function decodeArray(string $json): array
    {
        $value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($value)) {
            throw new JsonException('Ожидался JSON-объект или массив');
        }

        return $value;
    }
}
