<?php

declare(strict_types=1);

namespace CheckoApi\Console;

final readonly class Console
{
    /** @var array<string, CommandInterface> */
    private array $commands;

    /**
     * @param list<CommandInterface> $commands
     */
    public function __construct(array $commands, private Output $output)
    {
        $indexed = [];

        foreach ($commands as $command) {
            $indexed[$command->name()] = $command;
        }

        $this->commands = $indexed;
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? null;
        $command = $name === null ? null : $this->commands[$name] ?? null;

        if ($command === null) {
            if ($name !== null) {
                $this->output->error(sprintf('Неизвестная команда: %s', $name));
            }

            $this->help();

            return $name === null ? 0 : 1;
        }

        return $command->run(array_slice($argv, 2), $this->output);
    }

    private function help(): void
    {
        $this->output->line('Использование: bin/console <команда> [аргументы]');
        $this->output->line();

        foreach ($this->commands as $name => $command) {
            $this->output->line(sprintf('  %-16s %s', $name, $command->description()));
        }
    }
}
