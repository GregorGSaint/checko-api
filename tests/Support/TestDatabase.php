<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Support;

use CheckoApi\Persistence\Database;
use CheckoApi\Persistence\Migrator;
use PDO;

final class TestDatabase
{
    public static function create(): PDO
    {
        $pdo = Database::connect(':memory:');
        (new Migrator($pdo, dirname(__DIR__, 2) . '/migrations', new FrozenClock()))->migrate();

        return $pdo;
    }
}
