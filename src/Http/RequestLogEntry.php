<?php

declare(strict_types=1);

namespace CheckoApi\Http;

use DateTimeImmutable;

final readonly class RequestLogEntry
{
    public function __construct(
        public string $requestId,
        public ?int $tokenId,
        public string $method,
        public string $path,
        public int $status,
        public int $durationMs,
        public ?string $cacheStatus,
        public ?string $clientIp,
        public DateTimeImmutable $createdAt,
    ) {}
}
