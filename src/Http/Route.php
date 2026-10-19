<?php

declare(strict_types=1);

namespace CheckoApi\Http;

use Closure;

final readonly class Route
{
    private string $regex;

    /**
     * @param Closure(Request, array<string, string>): Response $handler
     */
    public function __construct(
        public string $method,
        public string $pattern,
        public Closure $handler,
        public string $description,
    ) {
        $this->regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
    }

    /**
     * @return array<string, string>|null
     */
    public function match(string $path): ?array
    {
        if (preg_match($this->regex, $path, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);
    }
}
