<?php

declare(strict_types=1);

namespace CheckoApi\Console;

use CheckoApi\Cache\CacheInterface;
use CheckoApi\Support\ClockInterface;

final readonly class PruneCacheCommand implements CommandInterface
{
    private const DEFAULT_DAYS = 30;

    public function __construct(
        private CacheInterface $cache,
        private ClockInterface $clock,
    ) {}

    public function name(): string
    {
        return 'cache:prune';
    }

    public function description(): string
    {
        return 'Удалить записи кэша, просроченные больше N дней назад: cache:prune [--days=30]';
    }

    public function run(array $arguments, Output $output): int
    {
        $days = self::DEFAULT_DAYS;

        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--days=')) {
                $days = max(0, (int) substr($argument, strlen('--days=')));
            }
        }

        $removed = $this->cache->pruneExpiredBefore($this->clock->now()->modify(sprintf('-%d days', $days)));
        $output->line(sprintf('Удалено записей: %d', $removed));

        return 0;
    }
}
