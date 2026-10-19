<?php

declare(strict_types=1);

namespace CheckoApi\Http\Middleware;

use CheckoApi\Http\Request;
use CheckoApi\Http\RequestHandlerInterface;
use CheckoApi\Http\Response;

final readonly class Pipeline implements RequestHandlerInterface
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private array $middleware,
        private RequestHandlerInterface $handler,
    ) {}

    public function handle(Request $request): Response
    {
        if ($this->middleware === []) {
            return $this->handler->handle($request);
        }

        [$current, $rest] = [$this->middleware[0], array_slice($this->middleware, 1)];

        return $current->process($request, new self($rest, $this->handler));
    }
}
