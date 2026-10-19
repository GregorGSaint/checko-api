<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Exception;

final class QuotaExhaustedException extends TemporaryFailureException
{
    public static function untilReset(int $todayRequestCount, int $retryAfter): self
    {
        return new self(sprintf('Суточная квота источника исчерпана: %d запросов', $todayRequestCount), $retryAfter);
    }
}
