<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Checko;

use CheckoApi\Checko\CheckoApiClient;
use CheckoApi\Checko\Endpoint;
use CheckoApi\Checko\Exception\AuthenticationFailedException;
use CheckoApi\Checko\Exception\InvalidResponseException;
use CheckoApi\Checko\Exception\NotFoundException;
use CheckoApi\Checko\Exception\QuotaExhaustedException;
use CheckoApi\Checko\Exception\RateLimitedException;
use CheckoApi\Checko\Exception\RequestRejectedException;
use CheckoApi\Checko\Exception\UpstreamUnavailableException;
use CheckoApi\Checko\Quota\QuotaGuard;
use CheckoApi\Checko\Quota\UpstreamCall;
use CheckoApi\Persistence\SqliteUpstreamCallLog;
use CheckoApi\Support\DailyWindow;
use CheckoApi\Tests\Support\FakeHttpClient;
use CheckoApi\Tests\Support\FrozenClock;
use CheckoApi\Tests\Support\TestDatabase;
use CheckoApi\Transport\HttpResponse;
use CheckoApi\Transport\TransportException;
use PHPUnit\Framework\TestCase;

final class CheckoApiClientTest extends TestCase
{
    private FakeHttpClient $http;
    private FrozenClock $clock;
    private SqliteUpstreamCallLog $calls;
    private CheckoApiClient $client;

    protected function setUp(): void
    {
        $this->http = new FakeHttpClient();
        $this->clock = new FrozenClock();
        $this->calls = new SqliteUpstreamCallLog(TestDatabase::create());
        $this->client = new CheckoApiClient(
            $this->http,
            new QuotaGuard($this->calls, $this->clock, DailyWindow::moscow(), 100),
            $this->clock,
            'https://api.checko.test/v2',
            'secret-key',
        );
    }

    public function testSendsKeyAndIdentifierToEndpoint(): void
    {
        $this->http->willReturnFixture('company.ok.full.json');

        $response = $this->client->fetch(Endpoint::Company, ['inn' => '7707083893']);

        self::assertSame('https://api.checko.test/v2/company', $this->http->requests[0]->url);
        self::assertSame(['key' => 'secret-key', 'inn' => '7707083893'], $this->http->requests[0]->query);
        self::assertSame('ПАО СБЕРБАНК', $response->data()['НаимСокр']);
    }

    public function testEmptyDataWithOkStatusMeansNotFound(): void
    {
        $this->http->willReturnFixture('company.not_found.json');

        $this->expectExceptionObject(new NotFoundException(Endpoint::Company, 'Не найдено ни одной организации с указанными реквизитами'));

        $this->client->fetch(Endpoint::Company, ['inn' => '9999999999']);
    }

    public function testFinancesWithoutCompanyBlockMeansNotFound(): void
    {
        $this->http->willReturnFixture('finances.not_found.json');

        $this->expectException(NotFoundException::class);

        $this->client->fetch(Endpoint::Finances, ['inn' => '9999999999']);
    }

    public function testBankWithoutStatementsIsNotNotFound(): void
    {
        $this->http->willReturnFixture('finances.empty.bank.json');

        $response = $this->client->fetch(Endpoint::Finances, ['inn' => '7707083893']);

        self::assertSame([], $response->data());
    }

    public function testRejectedParametersAreOurBug(): void
    {
        $this->http->willReturnFixture('company.error.missing_params.json', 400);

        $this->expectException(RequestRejectedException::class);

        $this->client->fetch(Endpoint::Company, []);
    }

    public function testInvalidKeyIsAuthenticationFailure(): void
    {
        $this->http->willReturnFixture('company.error.invalid_key.json', 401);

        $this->expectException(AuthenticationFailedException::class);

        $this->client->fetch(Endpoint::Company, ['inn' => '7707083893']);
    }

    public function testNonJsonBodyIsInvalidResponse(): void
    {
        $this->http->willReturnFixture('search.error.non_json_body.json', 400);

        $this->expectExceptionObject(InvalidResponseException::notJson(400));

        $this->client->fetch(Endpoint::Search, ['query' => 'яндекс']);
    }

    public function testTooManyRequestsCarriesRetryAfter(): void
    {
        $this->http->willReturn(new HttpResponse(429, '', ['retry-after' => '7']));

        try {
            $this->client->fetch(Endpoint::Company, ['inn' => '7707083893']);
            self::fail('Ожидалось исключение');
        } catch (RateLimitedException $exception) {
            self::assertSame(7, $exception->retryAfter);
        }
    }

    public function testServerErrorIsUnavailable(): void
    {
        $this->http->willReturn(new HttpResponse(503, '<html>Service Unavailable</html>'));

        $this->expectException(UpstreamUnavailableException::class);

        $this->client->fetch(Endpoint::Company, ['inn' => '7707083893']);
    }

    public function testTimeoutIsUnavailableWithTimeoutFlag(): void
    {
        $this->http->willReturn(new TransportException('Operation timed out', timedOut: true));

        try {
            $this->client->fetch(Endpoint::Company, ['inn' => '7707083893']);
            self::fail('Ожидалось исключение');
        } catch (UpstreamUnavailableException $exception) {
            self::assertTrue($exception->timedOut);
        }
    }

    public function testExhaustedQuotaStopsRequestBeforeNetwork(): void
    {
        $this->calls->record(new UpstreamCall('req', 'company', 1, 200, 'ok', 100, 0.0, 120, null, $this->clock->now()));

        try {
            $this->client->fetch(Endpoint::Company, ['inn' => '7707083893']);
            self::fail('Ожидалось исключение');
        } catch (QuotaExhaustedException $exception) {
            self::assertSame([], $this->http->requests);
            self::assertGreaterThan(0, $exception->retryAfter);
        }
    }

    public function testPositiveBalanceLiftsFreeLimit(): void
    {
        $this->calls->record(new UpstreamCall('req', 'company', 1, 200, 'ok', 150, 500.0, 120, null, $this->clock->now()));
        $this->http->willReturnFixture('company.ok.full.json');

        $this->client->fetch(Endpoint::Company, ['inn' => '7707083893']);

        self::assertCount(1, $this->http->requests);
    }

    public function testYesterdayQuotaDoesNotBlockToday(): void
    {
        $this->calls->record(new UpstreamCall('req', 'company', 1, 200, 'ok', 100, 0.0, 120, null, $this->clock->now()->modify('-1 day')));
        $this->http->willReturnFixture('company.ok.full.json');

        $this->client->fetch(Endpoint::Company, ['inn' => '7707083893']);

        self::assertCount(1, $this->http->requests);
    }
}
