<?php

declare(strict_types=1);

namespace CheckoApi\Cache;

use DateTimeImmutable;

interface CacheInterface
{
    public function get(string $key): ?CacheEntry;

    public function put(CacheEntry $entry): void;

    public function pruneExpiredBefore(DateTimeImmutable $moment): int;
}
