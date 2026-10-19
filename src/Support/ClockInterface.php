<?php

declare(strict_types=1);

namespace CheckoApi\Support;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
