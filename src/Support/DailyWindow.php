<?php

declare(strict_types=1);

namespace CheckoApi\Support;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

final readonly class DailyWindow
{
    public function __construct(private DateTimeZone $timezone) {}

    public static function moscow(): self
    {
        return new self(new DateTimeZone('Europe/Moscow'));
    }

    public function startOf(DateTimeImmutable $moment): DateTimeImmutable
    {
        return $moment->setTimezone($this->timezone)->setTime(0, 0);
    }

    public function secondsUntilReset(DateTimeImmutable $moment): int
    {
        $nextStart = $this->startOf($moment)->add(new DateInterval('P1D'));

        return max(1, $nextStart->getTimestamp() - $moment->getTimestamp());
    }
}
