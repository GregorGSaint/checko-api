<?php

declare(strict_types=1);

namespace CheckoApi\Http\Middleware;

use CheckoApi\Auth\TokenService;
use CheckoApi\Http\Request;
use CheckoApi\Http\RequestHandlerInterface;
use CheckoApi\Http\Response;
use CheckoApi\Support\RequestContext;

final readonly class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TokenService $tokens,
        private RequestContext $context,
        private string $protectedPrefix,
    ) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if ($request->path !== $this->protectedPrefix && !str_starts_with($request->path, $this->protectedPrefix . '/')) {
            return $next->handle($request);
        }

        $token = $this->tokens->authenticate($request->header('Authorization'));
        $this->context->authenticate($token->id);

        return $next->handle($request->withAttribute('token', $token));
    }
}
