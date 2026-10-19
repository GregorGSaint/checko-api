<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Api;

use CheckoApi\Bootstrap\Config;
use CheckoApi\Bootstrap\Services;
use CheckoApi\Http\Request;
use CheckoApi\Http\Response;
use CheckoApi\Support\Json;
use CheckoApi\Tests\Support\FakeHttpClient;
use CheckoApi\Tests\Support\FrozenClock;
use CheckoApi\Tests\Support\InMemoryLogger;
use CheckoApi\Tests\Support\RecordingSleeper;
use CheckoApi\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

abstract class ApiTestCase extends TestCase
{
    protected const API_KEY = 'test-checko-key';

    protected FakeHttpClient $upstream;
    protected FrozenClock $clock;
    protected InMemoryLogger $logger;
    protected RecordingSleeper $sleeper;
    protected Services $services;
    protected string $token;

    protected function setUp(): void
    {
        $this->upstream = new FakeHttpClient();
        $this->clock = new FrozenClock();
        $this->logger = new InMemoryLogger();
        $this->sleeper = new RecordingSleeper();
        $this->services = $this->services(self::API_KEY);
        $this->token = $this->services->tokens()->issue('tests', 1000);
    }

    protected function services(string $apiKey): Services
    {
        return new Services($this->config($apiKey), $this->clock, $this->upstream, $this->sleeper, $this->logger, TestDatabase::create());
    }

    protected function config(string $apiKey): Config
    {
        return Config::fromArray(dirname(__DIR__, 2), [
            'CHECKO_API_KEY' => $apiKey,
            'CHECKO_BASE_URL' => 'https://api.checko.test/v2',
            'DATABASE_PATH' => ':memory:',
        ]);
    }

    /**
     * @param array<string, string> $query
     */
    protected function get(string $path, array $query = [], ?string $token = null): Response
    {
        $headers = ['authorization' => 'Bearer ' . ($token ?? $this->token)];

        return $this->services->api()->handle(new Request('GET', $path, $query, $headers, '127.0.0.1'));
    }

    /**
     * @return array<mixed>
     */
    protected static function json(Response $response): array
    {
        return Json::decodeArray($response->body);
    }

    protected static function assertProblem(Response $response, int $status, string $code): void
    {
        self::assertSame($status, $response->status, $response->body);
        self::assertSame('application/problem+json; charset=utf-8', $response->header('Content-Type'));
        self::assertSame($code, self::json($response)['code'] ?? null);
    }
}
