<?php

declare(strict_types=1);

namespace CheckoApi\Auth;

final readonly class ApiToken
{
    public function __construct(
        public int $id,
        public string $name,
        public int $dailyLimit,
    ) {}
}
