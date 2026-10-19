<?php

declare(strict_types=1);

namespace CheckoApi\Http;

use CheckoApi\Http\Exception\MethodNotAllowedException;
use CheckoApi\Http\Exception\RouteNotFoundException;
use Closure;

final class Router implements RequestHandlerInterface
{
    /** @var list<Route> */
    private array $routes = [];

    /**
     * @param Closure(Request, array<string, string>): Response $handler
     */
    public function get(string $pattern, Closure $handler, string $description): self
    {
        $this->routes[] = new Route('GET', $pattern, $handler, $description);

        return $this;
    }

    /**
     * @return list<Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    public function handle(Request $request): Response
    {
        $allowed = [];

        foreach ($this->routes as $route) {
            $parameters = $route->match($request->path);

            if ($parameters === null) {
                continue;
            }

            if ($route->method === $request->method) {
                return ($route->handler)($request, $parameters);
            }

            $allowed[] = $route->method;
        }

        if ($allowed !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowed)));
        }

        throw new RouteNotFoundException($request->path);
    }
}
