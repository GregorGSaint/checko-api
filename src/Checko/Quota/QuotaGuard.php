<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Quota;

use CheckoApi\Checko\Exception\QuotaExhaustedException;
use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\DailyWindow;

final readonly class QuotaGuard
{
    public function __construct(
        private UpstreamCallLogInterface $calls,
        private ClockInterface $clock,
        private DailyWindow $window,
        public int $dailyLimit,
    ) {}

    public function ensureAvailable(): void
    {
        $now = $this->clock->now();
        $snapshot = $this->current();

        if ($snapshot !== null && $snapshot->isExhausted($this->dailyLimit)) {
            throw QuotaExhaustedException::untilReset($snapshot->todayRequestCount, $this->window->secondsUntilReset($now));
        }
    }

    public function current(): ?QuotaSnapshot
    {
        return $this->calls->latestQuotaSince($this->window->startOf($this->clock->now()));
    }
}
