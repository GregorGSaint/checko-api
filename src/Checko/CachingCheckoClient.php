<?php

declare(strict_types=1);

namespace CheckoApi\Checko;

use CheckoApi\Cache\CacheEntry;
use CheckoApi\Cache\CacheInterface;
use CheckoApi\Cache\CacheStatus;
use CheckoApi\Checko\Exception\NotFoundException;
use CheckoApi\Checko\Exception\TemporaryFailureException;
use CheckoApi\Logging\LoggerInterface;
use CheckoApi\Logging\LogLevel;
use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\Json;

final readonly class CachingCheckoClient implements CheckoClientInterface
{
    private const NOT_FOUND_TTL = 3_600;

    public function __construct(
        private CheckoClientInterface $inner,
        private CacheInterface $cache,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private string $namespace = 'live',
    ) {}

    public function fetch(Endpoint $endpoint, array $query, bool $bypassCache = false): CheckoResponse
    {
        $key = $this->key($endpoint, $query);
        $now = $this->clock->now();
        $cached = $this->cache->get($key);

        if (!$bypassCache && $cached !== null && $cached->isFreshAt($now)) {
            return $this->restore($endpoint, $cached, CacheStatus::Hit);
        }

        try {
            $response = $this->inner->fetch($endpoint, $query);
        } catch (NotFoundException $exception) {
            $this->cache->put(CacheEntry::notFound($key, $endpoint->value, $exception->getMessage(), $now, self::NOT_FOUND_TTL));

            throw $exception;
        } catch (TemporaryFailureException $exception) {
            if ($cached === null || $cached->isNotFound) {
                throw $exception;
            }

            $this->logger->log(LogLevel::Warning, 'Источник недоступен, отдана устаревшая копия', [
                'cache_key' => $key,
                'cached_at' => $cached->createdAt->format(DATE_ATOM),
                'reason' => $exception->getMessage(),
            ]);

            return $this->restore($endpoint, $cached, CacheStatus::Stale);
        }

        $this->cache->put(CacheEntry::found($key, $endpoint->value, Json::encode($response->body), $now, $endpoint->cacheTtl()));

        return $response;
    }

    /**
     * @param array<string, string> $query
     */
    private function key(Endpoint $endpoint, array $query): string
    {
        ksort($query);

        return $this->namespace . ':' . $endpoint->value . ':' . http_build_query($query);
    }

    private function restore(Endpoint $endpoint, CacheEntry $entry, CacheStatus $status): CheckoResponse
    {
        if ($entry->isNotFound) {
            throw new NotFoundException($endpoint, $entry->payload);
        }

        return new CheckoResponse($endpoint, Json::decodeArray($entry->payload), $entry->createdAt, $status);
    }
}
