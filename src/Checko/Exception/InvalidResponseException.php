<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Exception;

final class InvalidResponseException extends CheckoException
{
    public static function notJson(int $status): self
    {
        return new self(sprintf('Источник вернул не JSON (HTTP %d)', $status));
    }

    public static function missingField(string $field): self
    {
        return new self(sprintf('В ответе источника нет обязательного поля «%s»', $field));
    }

    public static function unexpected(int $status, ?string $sourceMessage): self
    {
        return new self(sprintf('Неожиданный ответ источника (HTTP %d): %s', $status, $sourceMessage ?? 'без пояснения'));
    }
}
