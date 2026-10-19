<?php

declare(strict_types=1);

namespace CheckoApi\Persistence;

use CheckoApi\Cache\CacheEntry;
use CheckoApi\Cache\CacheInterface;
use DateTimeImmutable;
use PDO;

final readonly class SqliteCache implements CacheInterface
{
    public function __construct(private PDO $pdo) {}

    public function get(string $key): ?CacheEntry
    {
        $statement = $this->pdo->prepare(
            'SELECT cache_key, resource, payload, is_not_found, created_at, expires_at
             FROM cache_entries
             WHERE cache_key = :key',
        );
        $statement->execute(['key' => $key]);

        /** @var array{cache_key: string, resource: string, payload: string, is_not_found: int, created_at: string, expires_at: string}|false $row */
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return new CacheEntry(
            $row['cache_key'],
            $row['resource'],
            $row['payload'],
            (bool) $row['is_not_found'],
            Timestamp::parse($row['created_at']),
            Timestamp::parse($row['expires_at']),
        );
    }

    public function put(CacheEntry $entry): void
    {
        $this->pdo->prepare(
            'INSERT INTO cache_entries (cache_key, resource, payload, is_not_found, created_at, expires_at)
             VALUES (:key, :resource, :payload, :is_not_found, :created_at, :expires_at)
             ON CONFLICT (cache_key) DO UPDATE SET
                 resource = excluded.resource,
                 payload = excluded.payload,
                 is_not_found = excluded.is_not_found,
                 created_at = excluded.created_at,
                 expires_at = excluded.expires_at',
        )->execute([
            'key' => $entry->key,
            'resource' => $entry->resource,
            'payload' => $entry->payload,
            'is_not_found' => (int) $entry->isNotFound,
            'created_at' => Timestamp::format($entry->createdAt),
            'expires_at' => Timestamp::format($entry->expiresAt),
        ]);
    }

    public function pruneExpiredBefore(DateTimeImmutable $moment): int
    {
        $statement = $this->pdo->prepare('DELETE FROM cache_entries WHERE expires_at < :moment');
        $statement->execute(['moment' => Timestamp::format($moment)]);

        return $statement->rowCount();
    }
}
