<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Quota;

use DateTimeImmutable;

final readonly class UpstreamCall
{
    public function __construct(
        public string $requestId,
        public string $endpoint,
        public int $attempt,
        public ?int $httpStatus,
        public ?string $metaStatus,
        public ?int $todayRequestCount,
        public ?float $balance,
        public int $durationMs,
        public ?string $error,
        public DateTimeImmutable $occurredAt,
    ) {}
}
