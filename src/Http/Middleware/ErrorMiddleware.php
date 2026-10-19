<?php

declare(strict_types=1);

namespace CheckoApi\Http\Middleware;

use CheckoApi\Http\ErrorHandler;
use CheckoApi\Http\Request;
use CheckoApi\Http\RequestHandlerInterface;
use CheckoApi\Http\Response;
use CheckoApi\Support\RequestContext;
use Throwable;

final readonly class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ErrorHandler $errors,
        private RequestContext $context,
    ) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        try {
            return $next->handle($request);
        } catch (Throwable $error) {
            return $this->errors->toProblem($error)->toResponse($this->context->requestId);
        }
    }
}
