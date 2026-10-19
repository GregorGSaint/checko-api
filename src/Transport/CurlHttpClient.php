<?php

declare(strict_types=1);

namespace CheckoApi\Transport;

final readonly class CurlHttpClient implements HttpClientInterface
{
    public function __construct(
        private float $timeoutSeconds = 10.0,
        private float $connectTimeoutSeconds = 5.0,
    ) {}

    public function send(HttpRequest $request): HttpResponse
    {
        $headers = [];
        $handle = curl_init($request->uri());

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT_MS => (int) ($this->timeoutSeconds * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($this->connectTimeoutSeconds * 1000),
            CURLOPT_HTTPHEADER => array_map(
                static fn(string $name, string $value): string => $name . ': ' . $value,
                array_keys($request->headers),
                $request->headers,
            ),
            CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
                $parts = explode(':', $line, 2);

                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ]);

        $body = curl_exec($handle);

        if (!is_string($body)) {
            throw new TransportException(curl_error($handle), curl_errno($handle) === CURLE_OPERATION_TIMEDOUT);
        }

        return new HttpResponse(curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $body, $headers);
    }
}
