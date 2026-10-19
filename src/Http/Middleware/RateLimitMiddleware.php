<?php

declare(strict_types=1);

namespace CheckoApi\Http\Middleware;

use CheckoApi\Auth\ApiToken;
use CheckoApi\Auth\TokenLimitExceededException;
use CheckoApi\Http\Request;
use CheckoApi\Http\RequestHandlerInterface;
use CheckoApi\Http\RequestLogInterface;
use CheckoApi\Http\Response;
use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\DailyWindow;

final readonly class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RequestLogInterface $log,
        private ClockInterface $clock,
        private DailyWindow $window,
    ) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $token = $request->attribute('token');

        if (!$token instanceof ApiToken) {
            return $next->handle($request);
        }

        $now = $this->clock->now();
        $used = $this->log->countForTokenSince($token->id, $this->window->startOf($now));

        if ($used >= $token->dailyLimit) {
            throw new TokenLimitExceededException($token->dailyLimit, $this->window->secondsUntilReset($now));
        }

        return $next->handle($request)
            ->withHeader('X-RateLimit-Limit', (string) $token->dailyLimit)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $token->dailyLimit - $used - 1));
    }
}
