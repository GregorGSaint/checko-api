<?php

declare(strict_types=1);

namespace CheckoApi\Checko;

use CheckoApi\Checko\Exception\AuthenticationFailedException;
use CheckoApi\Checko\Exception\InvalidResponseException;
use CheckoApi\Checko\Exception\NotFoundException;
use CheckoApi\Checko\Exception\RateLimitedException;
use CheckoApi\Checko\Exception\RequestRejectedException;
use CheckoApi\Checko\Exception\UpstreamUnavailableException;
use CheckoApi\Checko\Quota\QuotaGuard;
use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\Json;
use CheckoApi\Transport\HttpClientInterface;
use CheckoApi\Transport\HttpRequest;
use CheckoApi\Transport\HttpResponse;
use CheckoApi\Transport\TransportException;
use JsonException;

final readonly class CheckoApiClient implements CheckoClientInterface
{
    public function __construct(
        private HttpClientInterface $http,
        private QuotaGuard $quota,
        private ClockInterface $clock,
        private string $baseUrl,
        private string $apiKey,
    ) {}

    public function fetch(Endpoint $endpoint, array $query, bool $bypassCache = false): CheckoResponse
    {
        $this->quota->ensureAvailable();

        try {
            $response = $this->http->send(
                new HttpRequest('GET', $this->baseUrl . '/' . $endpoint->value, ['key' => $this->apiKey] + $query),
            );
        } catch (TransportException $exception) {
            throw UpstreamUnavailableException::fromTransport($exception);
        }

        $body = $this->parse($response);

        if ($endpoint->isNotFound($body)) {
            throw new NotFoundException($endpoint, Meta::fromBody($body)->message);
        }

        return new CheckoResponse($endpoint, $body, $this->clock->now());
    }

    /**
     * @return array<mixed>
     */
    private function parse(HttpResponse $response): array
    {
        if ($response->status === 429) {
            throw RateLimitedException::fromRetryAfter($response->header('retry-after'));
        }

        if ($response->status >= 500) {
            throw UpstreamUnavailableException::fromStatus($response->status);
        }

        try {
            $body = Json::decodeArray($response->body);
        } catch (JsonException) {
            throw InvalidResponseException::notJson($response->status);
        }

        $meta = Meta::fromBody($body);

        return match (true) {
            $response->status === 401 => throw new AuthenticationFailedException($meta->message),
            $response->status === 400 => throw new RequestRejectedException($meta->message),
            $response->status !== 200, !$meta->isOk() => throw InvalidResponseException::unexpected($response->status, $meta->message),
            default => $body,
        };
    }
}
