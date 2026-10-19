<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Quota;

use DateTimeImmutable;

interface UpstreamCallLogInterface
{
    public function record(UpstreamCall $call): void;

    public function latestQuotaSince(DateTimeImmutable $since): ?QuotaSnapshot;

    public function countSince(DateTimeImmutable $since): int;
}
