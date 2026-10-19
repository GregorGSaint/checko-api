<?php

declare(strict_types=1);

namespace CheckoApi\Http\Middleware;

use CheckoApi\Http\Request;
use CheckoApi\Http\RequestHandlerInterface;
use CheckoApi\Http\RequestLogEntry;
use CheckoApi\Http\RequestLogInterface;
use CheckoApi\Http\Response;
use CheckoApi\Logging\LoggerInterface;
use CheckoApi\Logging\LogLevel;
use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\RequestContext;
use Throwable;

final readonly class RequestLogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RequestLogInterface $log,
        private RequestContext $context,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $startedAt = hrtime(true);
        $response = $next->handle($request);

        $entry = new RequestLogEntry(
            requestId: $this->context->requestId,
            tokenId: $this->context->tokenId(),
            method: $request->method,
            path: $request->path,
            status: $response->status,
            durationMs: intdiv(hrtime(true) - $startedAt, 1_000_000),
            cacheStatus: $response->header('X-Cache'),
            clientIp: $request->clientIp,
            createdAt: $this->clock->now(),
        );

        try {
            $this->log->record($entry);
        } catch (Throwable $exception) {
            $this->logger->log(LogLevel::Error, 'Не удалось записать журнал запросов', ['reason' => $exception->getMessage()]);
        }

        $this->logger->log(LogLevel::Info, 'HTTP-запрос', [
            'method' => $entry->method,
            'path' => $entry->path,
            'status' => $entry->status,
            'duration_ms' => $entry->durationMs,
            'cache' => $entry->cacheStatus,
        ]);

        return $response;
    }
}
