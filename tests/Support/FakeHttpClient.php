<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Support;

use CheckoApi\Transport\HttpClientInterface;
use CheckoApi\Transport\HttpRequest;
use CheckoApi\Transport\HttpResponse;
use CheckoApi\Transport\TransportException;
use LogicException;

final class FakeHttpClient implements HttpClientInterface
{
    /** @var list<HttpResponse|TransportException> */
    private array $queue = [];

    /** @var list<HttpRequest> */
    public array $requests = [];

    public function willReturn(HttpResponse|TransportException ...$outcomes): self
    {
        array_push($this->queue, ...$outcomes);

        return $this;
    }

    public function willReturnFixture(string $name, int $status = 200): self
    {
        return $this->willReturn(new HttpResponse($status, Fixture::read($name)));
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->requests[] = $request;
        $outcome = array_shift($this->queue) ?? throw new LogicException('Очередь ответов пуста: ' . $request->url);

        if ($outcome instanceof TransportException) {
            throw $outcome;
        }

        return $outcome;
    }
}
