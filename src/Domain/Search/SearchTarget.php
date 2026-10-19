<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Search;

enum SearchTarget: string
{
    case Organization = 'org';
    case Entrepreneur = 'ent';
}
