<?php

declare(strict_types=1);

namespace CheckoApi\Http;

final readonly class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $headers = [],
        public ?string $clientIp = null,
        public array $attributes = [],
    ) {}

    public static function fromGlobals(): self
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (is_string($value) && str_starts_with($name, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($name, 5)))] = $value;
            }
        }

        $query = [];

        foreach ($_GET as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $query[$name] = $value;
            }
        }

        $uri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));

        return new self(
            method: is_string($_SERVER['REQUEST_METHOD'] ?? null) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET',
            path: $path === '/' ? $path : rtrim($path, '/'),
            query: $query,
            headers: $headers,
            clientIp: is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : null,
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function flag(string $name): bool
    {
        return in_array($this->query[$name] ?? '', ['1', 'true'], true);
    }

    public function attribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        return new self($this->method, $this->path, $this->query, $this->headers, $this->clientIp, [$name => $value] + $this->attributes);
    }
}
