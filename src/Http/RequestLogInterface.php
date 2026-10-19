<?php

declare(strict_types=1);

namespace CheckoApi\Http;

use DateTimeImmutable;

interface RequestLogInterface
{
    public function record(RequestLogEntry $entry): void;

    public function countForTokenSince(int $tokenId, DateTimeImmutable $since): int;
}
