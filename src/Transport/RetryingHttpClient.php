<?php

declare(strict_types=1);

namespace CheckoApi\Transport;

final readonly class RetryingHttpClient implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $inner,
        private RetryPolicy $policy,
        private SleeperInterface $sleeper,
    ) {}

    public function send(HttpRequest $request): HttpResponse
    {
        if ($request->method !== 'GET') {
            return $this->inner->send($request);
        }

        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $this->inner->send($request);
            } catch (TransportException $exception) {
                if (!$this->policy->allowsRetryAfter($attempt)) {
                    throw $exception;
                }

                $this->sleeper->sleep($this->policy->delayMs($attempt));

                continue;
            }

            if (!$this->policy->isRetryableStatus($response->status) || !$this->policy->allowsRetryAfter($attempt)) {
                return $response;
            }

            $this->sleeper->sleep($this->policy->delayMs($attempt, $response->header('retry-after')));
        }
    }
}
