<?php

declare(strict_types=1);

namespace CheckoApi\Http\Controller;

use CheckoApi\Checko\Quota\QuotaGuard;
use CheckoApi\Checko\Quota\UpstreamCallLogInterface;
use CheckoApi\Http\Request;
use CheckoApi\Http\Response;
use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\DailyWindow;
use PDO;
use Throwable;

final readonly class HealthController
{
    public function __construct(
        private PDO $pdo,
        private QuotaGuard $quota,
        private UpstreamCallLogInterface $calls,
        private ClockInterface $clock,
        private DailyWindow $window,
        private bool $demoMode,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public function show(Request $request, array $parameters): Response
    {
        $databaseAvailable = $this->databaseAvailable();

        return Response::json([
            'status' => $databaseAvailable ? 'ok' : 'degraded',
            'mode' => $this->demoMode ? 'demo' : 'live',
            'time' => $this->clock->now()->format(DATE_ATOM),
            'database' => $databaseAvailable ? 'ok' : 'unavailable',
            'upstream' => $databaseAvailable ? $this->upstream() : null,
        ], $databaseAvailable ? 200 : 503);
    }

    /**
     * @return array<string, mixed>
     */
    private function upstream(): array
    {
        $snapshot = $this->quota->current();

        return [
            'calls_today' => $this->calls->countSince($this->window->startOf($this->clock->now())),
            'daily_free_limit' => $this->quota->dailyLimit,
            'today_request_count' => $snapshot?->todayRequestCount,
            'remaining_free' => $snapshot?->remainingFree($this->quota->dailyLimit) ?? $this->quota->dailyLimit,
            'balance' => $snapshot?->balance,
            'observed_at' => $snapshot?->observedAt->format(DATE_ATOM),
        ];
    }

    private function databaseAvailable(): bool
    {
        try {
            return $this->pdo->query('SELECT 1') !== false;
        } catch (Throwable) {
            return false;
        }
    }
}
