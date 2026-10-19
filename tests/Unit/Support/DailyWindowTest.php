<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Support;

use CheckoApi\Support\DailyWindow;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class DailyWindowTest extends TestCase
{
    public function testMoscowDayStartsAtMidnightMoscowTime(): void
    {
        $moment = new DateTimeImmutable('2026-10-19 22:30:00', new DateTimeZone('UTC'));

        $start = DailyWindow::moscow()->startOf($moment);

        self::assertSame('2026-10-19T21:00:00+00:00', $start->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM));
    }

    public function testSecondsUntilMoscowMidnight(): void
    {
        $moment = new DateTimeImmutable('2026-10-19 20:00:00', new DateTimeZone('UTC'));

        self::assertSame(3600, DailyWindow::moscow()->secondsUntilReset($moment));
    }
}
