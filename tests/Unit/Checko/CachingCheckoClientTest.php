<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Checko;

use CheckoApi\Cache\CacheStatus;
use CheckoApi\Checko\CachingCheckoClient;
use CheckoApi\Checko\CheckoClientInterface;
use CheckoApi\Checko\CheckoResponse;
use CheckoApi\Checko\Endpoint;
use CheckoApi\Checko\Exception\CheckoException;
use CheckoApi\Checko\Exception\NotFoundException;
use CheckoApi\Checko\Exception\QuotaExhaustedException;
use CheckoApi\Checko\Exception\UpstreamUnavailableException;
use CheckoApi\Logging\LogLevel;
use CheckoApi\Persistence\SqliteCache;
use CheckoApi\Tests\Support\FrozenClock;
use CheckoApi\Tests\Support\InMemoryLogger;
use CheckoApi\Tests\Support\TestDatabase;
use CheckoApi\Transport\TransportException;
use PHPUnit\Framework\TestCase;

final class CachingCheckoClientTest extends TestCase
{
    private const QUERY = ['inn' => '7707083893'];

    private FrozenClock $clock;
    private InMemoryLogger $logger;

    /** @var list<CheckoResponse|CheckoException> */
    private array $outcomes = [];
    private int $calls = 0;
    private CachingCheckoClient $client;

    protected function setUp(): void
    {
        $this->clock = new FrozenClock();
        $this->logger = new InMemoryLogger();
        $this->client = new CachingCheckoClient($this->upstream(), new SqliteCache(TestDatabase::create()), $this->clock, $this->logger);
    }

    public function testSecondCallIsServedFromCache(): void
    {
        $this->outcomes = [$this->response()];

        $first = $this->client->fetch(Endpoint::Company, self::QUERY);
        $second = $this->client->fetch(Endpoint::Company, self::QUERY);

        self::assertSame(CacheStatus::Miss, $first->cacheStatus);
        self::assertSame(CacheStatus::Hit, $second->cacheStatus);
        self::assertSame($first->body, $second->body);
        self::assertSame(1, $this->calls);
    }

    public function testExpiredEntryIsRefetched(): void
    {
        $this->outcomes = [$this->response(), $this->response()];

        $this->client->fetch(Endpoint::Company, self::QUERY);
        $this->clock->advance('+25 hours');
        $refreshed = $this->client->fetch(Endpoint::Company, self::QUERY);

        self::assertSame(CacheStatus::Miss, $refreshed->cacheStatus);
        self::assertSame(2, $this->calls);
    }

    public function testBypassSkipsFreshEntry(): void
    {
        $this->outcomes = [$this->response(), $this->response()];

        $this->client->fetch(Endpoint::Company, self::QUERY);
        $this->client->fetch(Endpoint::Company, self::QUERY, bypassCache: true);

        self::assertSame(2, $this->calls);
    }

    public function testNotFoundIsCachedForAnHour(): void
    {
        $this->outcomes = [new NotFoundException(Endpoint::Company, 'Не найдено'), $this->response()];

        $this->assertNotFound();
        $this->assertNotFound();
        self::assertSame(1, $this->calls);

        $this->clock->advance('+61 minutes');
        $this->client->fetch(Endpoint::Company, self::QUERY);
        self::assertSame(2, $this->calls);
    }

    public function testStaleCopyIsServedWhenSourceIsDown(): void
    {
        $this->outcomes = [$this->response(), UpstreamUnavailableException::fromTransport(new TransportException('timeout', true))];

        $this->client->fetch(Endpoint::Company, self::QUERY);
        $this->clock->advance('+2 days');
        $stale = $this->client->fetch(Endpoint::Company, self::QUERY);

        self::assertSame(CacheStatus::Stale, $stale->cacheStatus);
        self::assertTrue($this->logger->has(LogLevel::Warning));
    }

    public function testStaleCopyCoversExhaustedQuota(): void
    {
        $this->outcomes = [$this->response(), QuotaExhaustedException::untilReset(100, 3600)];

        $this->client->fetch(Endpoint::Company, self::QUERY);
        $this->clock->advance('+2 days');

        self::assertSame(CacheStatus::Stale, $this->client->fetch(Endpoint::Company, self::QUERY)->cacheStatus);
    }

    public function testFailureWithoutCopyIsRethrown(): void
    {
        $this->outcomes = [UpstreamUnavailableException::fromStatus(502)];

        $this->expectException(UpstreamUnavailableException::class);

        $this->client->fetch(Endpoint::Company, self::QUERY);
    }

    private function assertNotFound(): void
    {
        try {
            $this->client->fetch(Endpoint::Company, self::QUERY);
            self::fail('Ожидалось NotFoundException');
        } catch (NotFoundException $exception) {
            self::assertSame('Не найдено', $exception->getMessage());
        }
    }

    private function response(): CheckoResponse
    {
        return new CheckoResponse(Endpoint::Company, ['data' => ['ОГРН' => '1027700132195']], $this->clock->now());
    }

    private function upstream(): CheckoClientInterface
    {
        return new class ($this) implements CheckoClientInterface {
            public function __construct(private readonly CachingCheckoClientTest $test) {}

            public function fetch(Endpoint $endpoint, array $query, bool $bypassCache = false): CheckoResponse
            {
                return $this->test->nextOutcome();
            }
        };
    }

    public function nextOutcome(): CheckoResponse
    {
        $this->calls++;
        $outcome = array_shift($this->outcomes) ?? self::fail('Источник вызван больше раз, чем ожидалось');

        if ($outcome instanceof CheckoException) {
            throw $outcome;
        }

        return $outcome;
    }
}
