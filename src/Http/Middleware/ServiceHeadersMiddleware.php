<?php

declare(strict_types=1);

namespace CheckoApi\Http\Middleware;

use CheckoApi\Http\Request;
use CheckoApi\Http\RequestHandlerInterface;
use CheckoApi\Http\Response;
use CheckoApi\Support\RequestContext;

final readonly class ServiceHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RequestContext $context,
        private bool $demoMode,
    ) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        return $next->handle($request)
            ->withHeader('X-Request-Id', $this->context->requestId)
            ->withHeader('X-Data-Source', $this->demoMode ? 'fixtures' : 'live');
    }
}
