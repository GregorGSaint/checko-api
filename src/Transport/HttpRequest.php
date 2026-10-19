<?php

declare(strict_types=1);

namespace CheckoApi\Transport;

final readonly class HttpRequest
{
    /**
     * @param non-empty-string $method
     * @param array<string, string> $query
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $method,
        public string $url,
        public array $query = [],
        public array $headers = [],
    ) {}

    public function uri(): string
    {
        return $this->query === [] ? $this->url : $this->url . '?' . http_build_query($this->query);
    }
}
