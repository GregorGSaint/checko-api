<?php

declare(strict_types=1);

namespace CheckoApi\Auth;

use DateTimeImmutable;

interface TokenStoreInterface
{
    public function findActiveByHash(string $hash): ?ApiToken;

    public function existsByHash(string $hash): bool;

    public function create(string $name, string $hash, int $dailyLimit, DateTimeImmutable $createdAt): ApiToken;

    public function markUsed(int $id, DateTimeImmutable $usedAt): void;
}
