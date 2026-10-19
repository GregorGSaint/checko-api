<?php

declare(strict_types=1);

namespace CheckoApi\Transport;

interface SleeperInterface
{
    public function sleep(int $milliseconds): void;
}
