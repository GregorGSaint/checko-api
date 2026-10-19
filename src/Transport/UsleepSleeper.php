<?php

declare(strict_types=1);

namespace CheckoApi\Transport;

final class UsleepSleeper implements SleeperInterface
{
    public function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
