<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Api;

use CheckoApi\Logging\LogLevel;
use CheckoApi\Transport\HttpResponse;
use CheckoApi\Transport\TransportException;

final class CompanyApiTest extends ApiTestCase
{
    public function testReturnsNormalizedCompanyAndCachesIt(): void
    {
        $this->upstream->willReturnFixture('company.ok.full.json');

        $first = $this->get('/v1/companies/7707083893');
        $second = $this->get('/v1/companies/7707083893');
        $body = self::json($first);

        self::assertSame(200, $first->status);
        self::assertSame('7707083893', $body['data']['inn']);
        self::assertSame('ПАО СБЕРБАНК', $body['data']['name']['short']);
        self::assertSame(584, $body['data']['collections']['trademarks']);
        self::assertSame('/v1/companies/7707083893/trademarks', $body['links']['trademarks']);
        self::assertSame('MISS', $first->header('X-Cache'));
        self::assertSame('HIT', $second->header('X-Cache'));
        self::assertSame('live', $first->header('X-Data-Source'));
        self::assertNotNull($first->header('X-Request-Id'));
        self::assertCount(1, $this->upstream->requests);
        self::assertSame(['key' => self::API_KEY, 'inn' => '7707083893'], $this->upstream->requests[0]->query);
    }

    public function testInvalidChecksumIsRejectedWithoutSpendingQuota(): void
    {
        $response = $this->get('/v1/companies/7707083894');

        self::assertProblem($response, 422, 'INVALID_IDENTIFIER');
        self::assertSame('id', self::json($response)['parameter']);
        self::assertSame([], $this->upstream->requests);
    }

    public function testEntrepreneurInnOnCompanyResourcePointsToRightOne(): void
    {
        $response = $this->get('/v1/companies/402907671076');

        self::assertProblem($response, 422, 'WRONG_RESOURCE');
        self::assertStringContainsString('/v1/entrepreneurs/402907671076', (string) self::json($response)['detail']);
    }

    public function testUnknownCompanyIsNotFound(): void
    {
        $this->upstream->willReturnFixture('company.not_found.json');

        $response = $this->get('/v1/companies/9999999999');

        self::assertProblem($response, 422, 'INVALID_IDENTIFIER');

        $response = $this->get('/v1/companies/7700000009');

        self::assertProblem($response, 404, 'COMPANY_NOT_FOUND');
        self::assertSame('Не найдено ни одной организации с указанными реквизитами', self::json($response)['detail']);
    }

    public function testSubResourcesReuseOneUpstreamCall(): void
    {
        $this->upstream->willReturnFixture('company.ok.full.json');

        $card = $this->get('/v1/companies/7707083893');
        $trademarks = $this->get('/v1/companies/7707083893/trademarks', ['page' => '6', 'limit' => '100']);
        $branches = $this->get('/v1/companies/7707083893/branches');
        $body = self::json($trademarks);

        self::assertSame(200, $card->status);
        self::assertCount(84, $body['data']);
        self::assertSame(['page' => 6, 'per_page' => 100, 'total' => 584, 'pages_total' => 6, 'has_next' => false], $body['meta']['pagination']);
        self::assertSame(88, self::json($branches)['meta']['pagination']['total']);
        self::assertCount(1, $this->upstream->requests);
    }

    public function testFinancesWithDerivedMetrics(): void
    {
        $this->upstream->willReturnFixture('finances.ok.yandex.json');

        $body = self::json($this->get('/v1/companies/7736207543/finances'));
        $latest = $body['data']['years'][0];

        self::assertSame(2024, $latest['year']);
        self::assertSame(544_581_317_000, $latest['indicators']['revenue']);
        self::assertSame(['net_margin' => -0.0214, 'revenue_growth' => 0.2905, 'debt_ratio' => 0.7714], $latest['metrics']);
    }

    public function testRawModeHidesAccountMeta(): void
    {
        $this->upstream->willReturnFixture('company.ok.full.json');

        $body = self::json($this->get('/v1/companies/7707083893', ['raw' => 'true']));

        self::assertSame('ПАО СБЕРБАНК', $body['data']['НаимСокр']);
        self::assertArrayNotHasKey('meta', $body);
    }

    public function testRejectedKeyIsGatewayErrorNotClientError(): void
    {
        $this->upstream->willReturnFixture('company.error.invalid_key.json', 401);

        self::assertProblem($this->get('/v1/companies/7707083893'), 502, 'UPSTREAM_AUTH_FAILED');
        self::assertTrue($this->logger->has(LogLevel::Critical));
    }

    public function testTimeoutIsRetriedThenReportedAsGatewayTimeout(): void
    {
        $this->upstream->willReturn(
            new TransportException('Operation timed out', timedOut: true),
            new TransportException('Operation timed out', timedOut: true),
            new TransportException('Operation timed out', timedOut: true),
        );

        self::assertProblem($this->get('/v1/companies/7707083893'), 504, 'UPSTREAM_TIMEOUT');
        self::assertCount(3, $this->upstream->requests);
        self::assertCount(2, $this->sleeper->delays);
    }

    public function testStaleCopyIsServedWhenSourceFails(): void
    {
        $this->upstream->willReturnFixture('company.ok.full.json');
        $this->get('/v1/companies/7707083893');
        $this->clock->advance('+2 days');
        $this->upstream->willReturn(new HttpResponse(502, ''), new HttpResponse(502, ''), new HttpResponse(502, ''));

        $response = $this->get('/v1/companies/7707083893');

        self::assertSame(200, $response->status);
        self::assertSame('STALE', $response->header('X-Cache'));
        self::assertSame('110 - "Response is Stale"', $response->header('Warning'));
    }

    public function testRefreshBypassesCache(): void
    {
        $this->upstream->willReturnFixture('company.ok.full.json')->willReturnFixture('company.ok.full.json');

        $this->get('/v1/companies/7707083893');
        $refreshed = $this->get('/v1/companies/7707083893', ['refresh' => 'true']);

        self::assertSame('MISS', $refreshed->header('X-Cache'));
        self::assertCount(2, $this->upstream->requests);
    }
}
