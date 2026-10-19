<?php

declare(strict_types=1);

namespace CheckoApi\Persistence;

use CheckoApi\Auth\ApiToken;
use CheckoApi\Auth\TokenStoreInterface;
use DateTimeImmutable;
use PDO;

final readonly class SqliteTokenStore implements TokenStoreInterface
{
    public function __construct(private PDO $pdo) {}

    public function findActiveByHash(string $hash): ?ApiToken
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, daily_limit FROM api_tokens WHERE token_hash = :hash AND is_active = 1',
        );
        $statement->execute(['hash' => $hash]);

        /** @var array{id: int, name: string, daily_limit: int}|false $row */
        $row = $statement->fetch();

        return $row === false ? null : new ApiToken($row['id'], $row['name'], $row['daily_limit']);
    }

    public function existsByHash(string $hash): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM api_tokens WHERE token_hash = :hash');
        $statement->execute(['hash' => $hash]);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $name, string $hash, int $dailyLimit, DateTimeImmutable $createdAt): ApiToken
    {
        $this->pdo->prepare(
            'INSERT INTO api_tokens (name, token_hash, daily_limit, created_at)
             VALUES (:name, :hash, :daily_limit, :created_at)',
        )->execute([
            'name' => $name,
            'hash' => $hash,
            'daily_limit' => $dailyLimit,
            'created_at' => Timestamp::format($createdAt),
        ]);

        return new ApiToken((int) $this->pdo->lastInsertId(), $name, $dailyLimit);
    }

    public function markUsed(int $id, DateTimeImmutable $usedAt): void
    {
        $this->pdo
            ->prepare('UPDATE api_tokens SET last_used_at = :used_at WHERE id = :id')
            ->execute(['used_at' => Timestamp::format($usedAt), 'id' => $id]);
    }
}
