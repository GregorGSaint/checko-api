<?php

declare(strict_types=1);

namespace CheckoApi\Console;

use CheckoApi\Persistence\Migrator;

final readonly class MigrateCommand implements CommandInterface
{
    public function __construct(private Migrator $migrator) {}

    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Применить миграции базы данных';
    }

    public function run(array $arguments, Output $output): int
    {
        $applied = $this->migrator->migrate();
        $output->line($applied === [] ? 'Схема актуальна' : 'Применены миграции: ' . implode(', ', $applied));

        return 0;
    }
}
