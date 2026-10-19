<?php

declare(strict_types=1);

namespace CheckoApi\Cache;

use DateTimeImmutable;

final readonly class CacheEntry
{
    public function __construct(
        public string $key,
        public string $resource,
        public string $payload,
        public bool $isNotFound,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $expiresAt,
    ) {}

    public static function found(string $key, string $resource, string $payload, DateTimeImmutable $now, int $ttl): self
    {
        return new self($key, $resource, $payload, false, $now, $now->modify(sprintf('+%d seconds', $ttl)));
    }

    public static function notFound(string $key, string $resource, string $message, DateTimeImmutable $now, int $ttl): self
    {
        return new self($key, $resource, $message, true, $now, $now->modify(sprintf('+%d seconds', $ttl)));
    }

    public function isFreshAt(DateTimeImmutable $moment): bool
    {
        return $moment < $this->expiresAt;
    }
}
