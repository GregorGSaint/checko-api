<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Quota;

use CheckoApi\Checko\Meta;
use CheckoApi\Logging\LoggerInterface;
use CheckoApi\Logging\LogLevel;
use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\RequestContext;
use CheckoApi\Transport\HttpClientInterface;
use CheckoApi\Transport\HttpRequest;
use CheckoApi\Transport\HttpResponse;
use CheckoApi\Transport\TransportException;

final class RecordingHttpClient implements HttpClientInterface
{
    /** @var array<string, int> */
    private array $attempts = [];

    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly UpstreamCallLogInterface $calls,
        private readonly RequestContext $context,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    public function send(HttpRequest $request): HttpResponse
    {
        $endpoint = basename((string) parse_url($request->url, PHP_URL_PATH));
        $attempt = $this->attempts[$request->uri()] = ($this->attempts[$request->uri()] ?? 0) + 1;
        $startedAt = hrtime(true);

        try {
            $response = $this->inner->send($request);
        } catch (TransportException $exception) {
            $this->record($endpoint, $attempt, $startedAt, null, new Meta(), $exception->getMessage());

            throw $exception;
        }

        $this->record($endpoint, $attempt, $startedAt, $response->status, Meta::fromJson($response->body), null);

        return $response;
    }

    private function record(string $endpoint, int $attempt, int $startedAt, ?int $status, Meta $meta, ?string $error): void
    {
        $call = new UpstreamCall(
            requestId: $this->context->requestId,
            endpoint: $endpoint,
            attempt: $attempt,
            httpStatus: $status,
            metaStatus: $meta->status,
            todayRequestCount: $meta->todayRequestCount,
            balance: $meta->balance,
            durationMs: intdiv(hrtime(true) - $startedAt, 1_000_000),
            error: $error,
            occurredAt: $this->clock->now(),
        );

        $this->calls->record($call);
        $this->logger->log($error === null ? LogLevel::Info : LogLevel::Warning, 'Запрос к источнику', [
            'endpoint' => $call->endpoint,
            'attempt' => $call->attempt,
            'http_status' => $call->httpStatus,
            'meta_status' => $call->metaStatus,
            'today_request_count' => $call->todayRequestCount,
            'duration_ms' => $call->durationMs,
            'error' => $call->error,
        ]);
    }
}
