<?php

declare(strict_types=1);

namespace CheckoApi\Http;

use CheckoApi\Auth\AuthenticationException;
use CheckoApi\Auth\TokenLimitExceededException;
use CheckoApi\Checko\Endpoint;
use CheckoApi\Checko\Exception\AuthenticationFailedException;
use CheckoApi\Checko\Exception\InvalidResponseException;
use CheckoApi\Checko\Exception\NotFoundException;
use CheckoApi\Checko\Exception\QuotaExhaustedException;
use CheckoApi\Checko\Exception\RateLimitedException;
use CheckoApi\Checko\Exception\RequestRejectedException;
use CheckoApi\Checko\Exception\TemporaryFailureException;
use CheckoApi\Checko\Exception\UpstreamUnavailableException;
use CheckoApi\Domain\Exception\ValidationException;
use CheckoApi\Http\Exception\MethodNotAllowedException;
use CheckoApi\Http\Exception\RouteNotFoundException;
use CheckoApi\Logging\LoggerInterface;
use CheckoApi\Logging\LogLevel;
use Throwable;

final readonly class ErrorHandler
{
    public function __construct(private LoggerInterface $logger) {}

    public function toProblem(Throwable $error): Problem
    {
        $problem = match (true) {
            $error instanceof ValidationException
                => new Problem(422, $error->errorCode, $error->getMessage(), extensions: ['parameter' => $error->parameter]),
            $error instanceof NotFoundException
                => new Problem(404, self::notFoundCode($error->endpoint), $error->getMessage()),
            $error instanceof RouteNotFoundException
                => new Problem(404, 'ROUTE_NOT_FOUND', $error->getMessage()),
            $error instanceof MethodNotAllowedException
                => new Problem(405, 'METHOD_NOT_ALLOWED', $error->getMessage(), ['Allow' => implode(', ', $error->allowed)]),
            $error instanceof AuthenticationException
                => new Problem(401, 'UNAUTHORIZED', $error->getMessage(), ['WWW-Authenticate' => 'Bearer realm="checko-api"']),
            $error instanceof TokenLimitExceededException
                => new Problem(429, 'TOKEN_LIMIT_EXCEEDED', $error->getMessage(), ['Retry-After' => (string) $error->retryAfter]),
            $error instanceof QuotaExhaustedException
                => new Problem(503, 'QUOTA_EXHAUSTED', 'Суточная квота запросов к источнику исчерпана', self::retryAfter($error)),
            $error instanceof RateLimitedException
                => new Problem(503, 'UPSTREAM_RATE_LIMITED', 'Источник временно ограничил частоту запросов', self::retryAfter($error)),
            $error instanceof UpstreamUnavailableException && $error->timedOut
                => new Problem(504, 'UPSTREAM_TIMEOUT', 'Источник данных не ответил вовремя, повторите запрос позже'),
            $error instanceof UpstreamUnavailableException
                => new Problem(502, 'UPSTREAM_UNAVAILABLE', 'Источник данных ответил ошибкой, повторите запрос позже'),
            $error instanceof AuthenticationFailedException
                => new Problem(502, 'UPSTREAM_AUTH_FAILED', 'Сервис не смог авторизоваться в источнике данных'),
            $error instanceof InvalidResponseException
                => new Problem(502, 'UPSTREAM_INVALID_RESPONSE', 'Источник вернул ответ в неожиданном формате'),
            $error instanceof RequestRejectedException
                => new Problem(500, 'UPSTREAM_REJECTED_REQUEST', 'Источник отклонил запрос сервиса, это ошибка на нашей стороне'),
            default => new Problem(500, 'INTERNAL_ERROR', 'Внутренняя ошибка сервиса'),
        };

        $this->report($error, $problem);

        return $problem;
    }

    private function report(Throwable $error, Problem $problem): void
    {
        $level = match (true) {
            $error instanceof AuthenticationFailedException, $error instanceof RequestRejectedException => LogLevel::Critical,
            $error instanceof TemporaryFailureException => LogLevel::Warning,
            $problem->status >= 500 => LogLevel::Error,
            default => null,
        };

        if ($level !== null) {
            $this->logger->log($level, $error->getMessage(), [
                'code' => $problem->code,
                'status' => $problem->status,
                'exception' => $error::class,
                'location' => $error->getFile() . ':' . $error->getLine(),
            ]);
        }
    }

    private static function notFoundCode(Endpoint $endpoint): string
    {
        return match ($endpoint) {
            Endpoint::Company, Endpoint::Finances => 'COMPANY_NOT_FOUND',
            Endpoint::Entrepreneur => 'ENTREPRENEUR_NOT_FOUND',
            Endpoint::Search => 'NOT_FOUND',
        };
    }

    /**
     * @return array<string, string>
     */
    private static function retryAfter(TemporaryFailureException $error): array
    {
        return $error->retryAfter === null ? [] : ['Retry-After' => (string) $error->retryAfter];
    }
}
