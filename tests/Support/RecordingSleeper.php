<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Support;

use CheckoApi\Transport\SleeperInterface;

final class RecordingSleeper implements SleeperInterface
{
    /** @var list<int> */
    public array $delays = [];

    public function sleep(int $milliseconds): void
    {
        $this->delays[] = $milliseconds;
    }
}
