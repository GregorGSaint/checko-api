<?php

declare(strict_types=1);

namespace CheckoApi\Logging;

use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\Json;
use CheckoApi\Support\RequestContext;

final readonly class StreamLogger implements LoggerInterface
{
    /** @var list<string> */
    private array $secrets;

    /**
     * @param list<string> $secrets
     */
    public function __construct(
        private string $stream,
        private RequestContext $context,
        private ClockInterface $clock,
        array $secrets = [],
    ) {
        $this->secrets = array_values(array_filter($secrets, static fn(string $secret): bool => $secret !== ''));
    }

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        $line = Json::encode([
            'time' => $this->clock->now()->format(DATE_RFC3339_EXTENDED),
            'level' => $level->value,
            'request_id' => $this->context->requestId,
            'message' => $message,
        ] + $context);

        file_put_contents($this->stream, str_replace($this->secrets, '***', $line) . PHP_EOL, FILE_APPEND);
    }
}
