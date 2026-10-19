<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Api;

use CheckoApi\Bootstrap\Services;
use CheckoApi\Http\Request;
use CheckoApi\Tests\Support\TestDatabase;

final class ServiceApiTest extends ApiTestCase
{
    public function testHealthIsPublicAndReportsQuota(): void
    {
        $this->upstream->willReturnFixture('company.ok.full.json');
        $this->get('/v1/companies/7707083893');

        $response = $this->services->api()->handle(new Request('GET', '/health'));
        $body = self::json($response);

        self::assertSame(200, $response->status);
        self::assertSame('live', $body['mode']);
        self::assertSame(1, $body['upstream']['calls_today']);
        self::assertSame(1, $body['upstream']['today_request_count']);
        self::assertSame(99, $body['upstream']['remaining_free']);
    }

    public function testMissingTokenIsUnauthorized(): void
    {
        $response = $this->services->api()->handle(new Request('GET', '/v1/companies/7707083893'));

        self::assertProblem($response, 401, 'UNAUTHORIZED');
        self::assertSame('Bearer realm="checko-api"', $response->header('WWW-Authenticate'));
    }

    public function testUnknownTokenIsUnauthorized(): void
    {
        self::assertProblem($this->get('/v1/companies/7707083893', token: 'ck_unknown'), 401, 'UNAUTHORIZED');
    }

    public function testTokenDailyLimit(): void
    {
        $limited = $this->services->tokens()->issue('limited', 2);

        $first = $this->get('/v1', token: $limited);
        $this->get('/v1', token: $limited);
        $third = $this->get('/v1', token: $limited);

        self::assertSame('1', $first->header('X-RateLimit-Remaining'));
        self::assertProblem($third, 429, 'TOKEN_LIMIT_EXCEEDED');
        self::assertNotNull($third->header('Retry-After'));
    }

    public function testIndexListsResources(): void
    {
        $paths = array_column(self::json($this->get('/v1'))['data']['resources'], 'path');

        self::assertContains('/v1/companies/{id}', $paths);
        self::assertContains('/v1/companies/{id}/finances', $paths);
        self::assertContains('/v1/search', $paths);
    }

    public function testUnknownRouteAndWrongMethod(): void
    {
        self::assertProblem($this->get('/v1/unknown'), 404, 'ROUTE_NOT_FOUND');

        $response = $this->services->api()->handle(
            new Request('POST', '/v1/companies/7707083893', [], ['authorization' => 'Bearer ' . $this->token]),
        );

        self::assertProblem($response, 405, 'METHOD_NOT_ALLOWED');
        self::assertSame('GET', $response->header('Allow'));
    }

    public function testSearchValidatesBeforeCallingSource(): void
    {
        $response = $this->get('/v1/search', ['by' => 'name', 'query' => 'abc']);

        self::assertProblem($response, 422, 'INVALID_PARAMETER');
        self::assertSame([], $this->upstream->requests);
    }

    public function testSearchReturnsLinkedItemsAndPagination(): void
    {
        $this->upstream->willReturnFixture('search.ok.org.json');

        $body = self::json($this->get('/v1/search', ['by' => 'name', 'query' => 'яндекс', 'limit' => '3']));

        self::assertSame('/v1/companies/7736207543', $body['data'][0]['links']['self']);
        self::assertSame(53, $body['meta']['pagination']['total']);
        self::assertSame('/v1/search?page=2&by=name&query=%D1%8F%D0%BD%D0%B4%D0%B5%D0%BA%D1%81&limit=3', $body['links']['next']);
        self::assertSame(['key' => self::API_KEY, 'by' => 'name', 'obj' => 'org', 'query' => 'яндекс', 'page' => '1', 'limit' => '3'], $this->upstream->requests[0]->query);
    }

    public function testEntrepreneurCard(): void
    {
        $this->upstream->willReturnFixture('entrepreneur.ok.json');

        $body = self::json($this->get('/v1/entrepreneurs/310402910200035'));

        self::assertSame('Морозов Андрей Сергеевич', $body['data']['full_name']);
        self::assertSame(['ogrn' => '310402910200035'], array_diff_key($this->upstream->requests[0]->query, ['key' => true]));
    }

    public function testDemoModeServesRecordedResponses(): void
    {
        $this->services = $this->services('');
        $this->token = $this->services->tokens()->issue('demo', 100);

        $known = $this->get('/v1/companies/1027700229193');
        $unknown = $this->get('/v1/companies/7700000009');

        self::assertSame(200, $known->status);
        self::assertSame('fixtures', $known->header('X-Data-Source'));
        self::assertTrue(self::json($known)['data']['managers'][0]['access_restricted']);
        self::assertProblem($unknown, 404, 'COMPANY_NOT_FOUND');
        self::assertSame([], $this->upstream->requests);
    }

    public function testDemoCacheNeverLeaksIntoLiveMode(): void
    {
        $pdo = TestDatabase::create();
        $demo = new Services($this->config(''), $this->clock, $this->upstream, $this->sleeper, $this->logger, $pdo);
        $live = new Services($this->config(self::API_KEY), $this->clock, $this->upstream, $this->sleeper, $this->logger, $pdo);
        $token = $live->tokens()->issue('shared', 100);
        $request = new Request('GET', '/v1/companies/7707083893', [], ['authorization' => 'Bearer ' . $token]);
        $this->upstream->willReturnFixture('company.ok.full.json');

        $demo->api()->handle($request);
        $response = $live->api()->handle($request);

        self::assertSame('MISS', $response->header('X-Cache'));
        self::assertCount(1, $this->upstream->requests);
    }
}
