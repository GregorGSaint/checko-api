<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Support;

use CheckoApi\Support\ClockInterface;
use DateTimeImmutable;
use DateTimeZone;

final class FrozenClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(string $moment = '2026-10-19 12:00:00')
    {
        $this->now = new DateTimeImmutable($moment, new DateTimeZone('UTC'));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }
}
