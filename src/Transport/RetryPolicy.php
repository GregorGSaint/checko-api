<?php

declare(strict_types=1);

namespace CheckoApi\Transport;

final readonly class RetryPolicy
{
    public function __construct(
        public int $maxRetries = 2,
        public int $baseDelayMs = 200,
        public int $maxDelayMs = 2000,
    ) {}

    public function allowsRetryAfter(int $attempt): bool
    {
        return $attempt <= $this->maxRetries;
    }

    public function isRetryableStatus(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    public function delayMs(int $attempt, ?string $retryAfter = null): int
    {
        if ($retryAfter !== null && ctype_digit($retryAfter)) {
            return min((int) $retryAfter * 1000, $this->maxDelayMs);
        }

        $ceiling = min($this->baseDelayMs * 2 ** ($attempt - 1), $this->maxDelayMs);
        $half = intdiv($ceiling, 2);

        return $half + random_int(0, $ceiling - $half);
    }
}
