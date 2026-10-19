<?php

declare(strict_types=1);

namespace CheckoApi\Console;

interface CommandInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * @param list<string> $arguments
     */
    public function run(array $arguments, Output $output): int;
}
