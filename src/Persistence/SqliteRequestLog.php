<?php

declare(strict_types=1);

namespace CheckoApi\Persistence;

use CheckoApi\Http\RequestLogEntry;
use CheckoApi\Http\RequestLogInterface;
use DateTimeImmutable;
use PDO;

final readonly class SqliteRequestLog implements RequestLogInterface
{
    public function __construct(private PDO $pdo) {}

    public function record(RequestLogEntry $entry): void
    {
        $this->pdo->prepare(
            'INSERT INTO request_log
                 (request_id, token_id, method, path, status, duration_ms, cache_status, client_ip, created_at)
             VALUES
                 (:request_id, :token_id, :method, :path, :status, :duration_ms, :cache_status, :client_ip, :created_at)',
        )->execute([
            'request_id' => $entry->requestId,
            'token_id' => $entry->tokenId,
            'method' => $entry->method,
            'path' => $entry->path,
            'status' => $entry->status,
            'duration_ms' => $entry->durationMs,
            'cache_status' => $entry->cacheStatus,
            'client_ip' => $entry->clientIp,
            'created_at' => Timestamp::format($entry->createdAt),
        ]);
    }

    public function countForTokenSince(int $tokenId, DateTimeImmutable $since): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM request_log WHERE token_id = :token_id AND created_at >= :since',
        );
        $statement->execute(['token_id' => $tokenId, 'since' => Timestamp::format($since)]);

        return (int) $statement->fetchColumn();
    }
}
