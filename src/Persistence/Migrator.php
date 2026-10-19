<?php

declare(strict_types=1);

namespace CheckoApi\Persistence;

use CheckoApi\Support\ClockInterface;
use PDO;
use Throwable;

final readonly class Migrator
{
    public function __construct(
        private PDO $pdo,
        private string $directory,
        private ClockInterface $clock,
    ) {}

    /**
     * @return list<string>
     */
    public function migrate(): array
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, applied_at TEXT NOT NULL)',
        );

        $statement = $this->pdo->query('SELECT version FROM schema_migrations');
        $applied = $statement === false ? [] : $statement->fetchAll(PDO::FETCH_COLUMN);
        $newlyApplied = [];

        foreach (glob($this->directory . '/*.sql') ?: [] as $file) {
            $version = basename($file, '.sql');

            if (in_array($version, $applied, true)) {
                continue;
            }

            $this->apply($version, (string) file_get_contents($file));
            $newlyApplied[] = $version;
        }

        return $newlyApplied;
    }

    private function apply(string $version, string $sql): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec($sql);
            $this->pdo
                ->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (:version, :applied_at)')
                ->execute(['version' => $version, 'applied_at' => Timestamp::format($this->clock->now())]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }
}
