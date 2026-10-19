<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Persistence;

use CheckoApi\Cache\CacheEntry;
use CheckoApi\Persistence\Migrator;
use CheckoApi\Persistence\SqliteCache;
use CheckoApi\Tests\Support\FrozenClock;
use CheckoApi\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

final class SqliteCacheTest extends TestCase
{
    public function testStoresAndOverwritesEntries(): void
    {
        $clock = new FrozenClock();
        $cache = new SqliteCache(TestDatabase::create());

        $cache->put(CacheEntry::found('company:inn=1', 'company', '{"v":1}', $clock->now(), 60));
        $cache->put(CacheEntry::found('company:inn=1', 'company', '{"v":2}', $clock->now(), 60));
        $entry = $cache->get('company:inn=1');

        self::assertNotNull($entry);
        self::assertSame('{"v":2}', $entry->payload);
        self::assertTrue($entry->isFreshAt($clock->now()));
        self::assertFalse($entry->isFreshAt($clock->now()->modify('+61 seconds')));
        self::assertNull($cache->get('company:inn=2'));
    }

    public function testPrunesOnlyExpiredEntries(): void
    {
        $clock = new FrozenClock();
        $cache = new SqliteCache(TestDatabase::create());
        $cache->put(CacheEntry::found('old', 'company', '{}', $clock->now()->modify('-2 days'), 60));
        $cache->put(CacheEntry::found('new', 'company', '{}', $clock->now(), 3600));

        self::assertSame(1, $cache->pruneExpiredBefore($clock->now()));
        self::assertNull($cache->get('old'));
        self::assertNotNull($cache->get('new'));
    }

    public function testMigrationsAreAppliedOnce(): void
    {
        $pdo = TestDatabase::create();

        self::assertSame([], (new Migrator($pdo, dirname(__DIR__, 3) . '/migrations', new FrozenClock()))->migrate());
    }
}
