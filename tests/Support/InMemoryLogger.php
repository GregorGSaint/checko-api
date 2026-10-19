<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Support;

use CheckoApi\Logging\LoggerInterface;
use CheckoApi\Logging\LogLevel;

final class InMemoryLogger implements LoggerInterface
{
    /** @var list<array{level: LogLevel, message: string, context: array<string, mixed>}> */
    public array $records = [];

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    public function has(LogLevel $level): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level) {
                return true;
            }
        }

        return false;
    }
}
