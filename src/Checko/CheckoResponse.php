<?php

declare(strict_types=1);

namespace CheckoApi\Checko;

use CheckoApi\Cache\CacheStatus;
use DateTimeImmutable;

final readonly class CheckoResponse
{
    /**
     * @param array<mixed> $body
     */
    public function __construct(
        public Endpoint $endpoint,
        public array $body,
        public DateTimeImmutable $fetchedAt,
        public CacheStatus $cacheStatus = CacheStatus::Miss,
    ) {}

    /**
     * @return array<mixed>
     */
    public function data(): array
    {
        return is_array($this->body['data'] ?? null) ? $this->body['data'] : [];
    }

    /**
     * @return array<mixed>
     */
    public function withoutAccountMeta(): array
    {
        $body = $this->body;
        unset($body['meta']);

        return $body;
    }
}
