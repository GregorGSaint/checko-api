<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Exception;

final class RateLimitedException extends TemporaryFailureException
{
    private const DEFAULT_RETRY_AFTER = 60;

    public static function fromRetryAfter(?string $header): self
    {
        $seconds = $header !== null && ctype_digit($header) ? (int) $header : self::DEFAULT_RETRY_AFTER;

        return new self('Источник ограничил частоту запросов (HTTP 429)', $seconds);
    }
}
