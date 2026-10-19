<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Quota;

use DateTimeImmutable;

final readonly class QuotaSnapshot
{
    public function __construct(
        public int $todayRequestCount,
        public float $balance,
        public DateTimeImmutable $observedAt,
    ) {}

    public function isExhausted(int $dailyLimit): bool
    {
        return $this->todayRequestCount >= $dailyLimit && $this->balance <= 0.0;
    }

    public function remainingFree(int $dailyLimit): int
    {
        return max(0, $dailyLimit - $this->todayRequestCount);
    }
}
