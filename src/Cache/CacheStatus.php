<?php

declare(strict_types=1);

namespace CheckoApi\Cache;

enum CacheStatus: string
{
    case Hit = 'HIT';
    case Miss = 'MISS';
    case Stale = 'STALE';
}
