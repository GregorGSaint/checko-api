<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Transport;

use CheckoApi\Tests\Support\FakeHttpClient;
use CheckoApi\Tests\Support\RecordingSleeper;
use CheckoApi\Transport\HttpRequest;
use CheckoApi\Transport\HttpResponse;
use CheckoApi\Transport\RetryingHttpClient;
use CheckoApi\Transport\RetryPolicy;
use CheckoApi\Transport\TransportException;
use PHPUnit\Framework\TestCase;

final class RetryingHttpClientTest extends TestCase
{
    private FakeHttpClient $inner;
    private RecordingSleeper $sleeper;
    private RetryingHttpClient $client;

    protected function setUp(): void
    {
        $this->inner = new FakeHttpClient();
        $this->sleeper = new RecordingSleeper();
        $this->client = new RetryingHttpClient($this->inner, new RetryPolicy(maxRetries: 2, baseDelayMs: 100, maxDelayMs: 1000), $this->sleeper);
    }

    public function testRetriesServerErrorsUntilSuccess(): void
    {
        $this->inner->willReturn(new HttpResponse(502, ''), new HttpResponse(503, ''), new HttpResponse(200, '{}'));

        $response = $this->client->send(new HttpRequest('GET', 'https://example.test'));

        self::assertSame(200, $response->status);
        self::assertCount(3, $this->inner->requests);
        self::assertCount(2, $this->sleeper->delays);
        self::assertGreaterThanOrEqual(50, $this->sleeper->delays[0]);
        self::assertLessThanOrEqual(100, $this->sleeper->delays[0]);
        self::assertGreaterThanOrEqual(100, $this->sleeper->delays[1]);
        self::assertLessThanOrEqual(200, $this->sleeper->delays[1]);
    }

    public function testReturnsLastResponseWhenRetriesAreExhausted(): void
    {
        $this->inner->willReturn(new HttpResponse(429, ''), new HttpResponse(429, ''), new HttpResponse(429, ''));

        $response = $this->client->send(new HttpRequest('GET', 'https://example.test'));

        self::assertSame(429, $response->status);
        self::assertCount(3, $this->inner->requests);
    }

    public function testHonoursRetryAfterHeaderWithinCeiling(): void
    {
        $this->inner->willReturn(new HttpResponse(429, '', ['retry-after' => '5']), new HttpResponse(200, '{}'));

        $this->client->send(new HttpRequest('GET', 'https://example.test'));

        self::assertSame([1000], $this->sleeper->delays);
    }

    public function testNeverRetriesClientErrors(): void
    {
        $this->inner->willReturn(new HttpResponse(404, ''));

        $response = $this->client->send(new HttpRequest('GET', 'https://example.test'));

        self::assertSame(404, $response->status);
        self::assertSame([], $this->sleeper->delays);
    }

    public function testRetriesNetworkFailuresAndRethrowsTheLast(): void
    {
        $this->inner->willReturn(
            new TransportException('Connection reset'),
            new TransportException('Connection reset'),
            new TransportException('Operation timed out', timedOut: true),
        );

        try {
            $this->client->send(new HttpRequest('GET', 'https://example.test'));
            self::fail('Ожидалось исключение транспорта');
        } catch (TransportException $exception) {
            self::assertTrue($exception->timedOut);
        }

        self::assertCount(3, $this->inner->requests);
    }

    public function testDoesNotRetryNonIdempotentMethods(): void
    {
        $this->inner->willReturn(new HttpResponse(503, ''));

        $response = $this->client->send(new HttpRequest('POST', 'https://example.test'));

        self::assertSame(503, $response->status);
        self::assertCount(1, $this->inner->requests);
    }
}
