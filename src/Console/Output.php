<?php

declare(strict_types=1);

namespace CheckoApi\Console;

final readonly class Output
{
    /**
     * @param resource $stdout
     * @param resource $stderr
     */
    public function __construct(
        private mixed $stdout = STDOUT,
        private mixed $stderr = STDERR,
    ) {}

    public function line(string $message = ''): void
    {
        fwrite($this->stdout, $message . PHP_EOL);
    }

    public function error(string $message): void
    {
        fwrite($this->stderr, $message . PHP_EOL);
    }
}
