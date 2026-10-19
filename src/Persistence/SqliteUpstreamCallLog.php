<?php

declare(strict_types=1);

namespace CheckoApi\Persistence;

use CheckoApi\Checko\Quota\QuotaSnapshot;
use CheckoApi\Checko\Quota\UpstreamCall;
use CheckoApi\Checko\Quota\UpstreamCallLogInterface;
use DateTimeImmutable;
use PDO;

final readonly class SqliteUpstreamCallLog implements UpstreamCallLogInterface
{
    public function __construct(private PDO $pdo) {}

    public function record(UpstreamCall $call): void
    {
        $this->pdo->prepare(
            'INSERT INTO upstream_calls
                 (request_id, endpoint, attempt, http_status, meta_status, today_request_count, balance, duration_ms, error, created_at)
             VALUES
                 (:request_id, :endpoint, :attempt, :http_status, :meta_status, :today_request_count, :balance, :duration_ms, :error, :created_at)',
        )->execute([
            'request_id' => $call->requestId,
            'endpoint' => $call->endpoint,
            'attempt' => $call->attempt,
            'http_status' => $call->httpStatus,
            'meta_status' => $call->metaStatus,
            'today_request_count' => $call->todayRequestCount,
            'balance' => $call->balance,
            'duration_ms' => $call->durationMs,
            'error' => $call->error,
            'created_at' => Timestamp::format($call->occurredAt),
        ]);
    }

    public function latestQuotaSince(DateTimeImmutable $since): ?QuotaSnapshot
    {
        $statement = $this->pdo->prepare(
            'SELECT today_request_count, balance, created_at
             FROM upstream_calls
             WHERE created_at >= :since AND today_request_count IS NOT NULL
             ORDER BY id DESC
             LIMIT 1',
        );
        $statement->execute(['since' => Timestamp::format($since)]);

        /** @var array{today_request_count: int, balance: float|int|null, created_at: string}|false $row */
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return new QuotaSnapshot($row['today_request_count'], (float) ($row['balance'] ?? 0), Timestamp::parse($row['created_at']));
    }

    public function countSince(DateTimeImmutable $since): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM upstream_calls WHERE created_at >= :since');
        $statement->execute(['since' => Timestamp::format($since)]);

        return (int) $statement->fetchColumn();
    }
}
