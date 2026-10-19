<?php

declare(strict_types=1);

namespace CheckoApi\Bootstrap;

final readonly class Config
{
    public function __construct(
        public string $projectDir,
        public string $checkoApiKey,
        public string $checkoBaseUrl,
        public float $checkoTimeout,
        public int $checkoDailyLimit,
        public string $databasePath,
        public string $logStream,
        public string $demoToken,
        public int $tokenDailyLimit,
    ) {}

    public static function load(string $projectDir): self
    {
        $file = is_readable($projectDir . '/.env') ? '/.env' : '/.env.example';
        $environment = array_filter(getenv(), static fn(string $value): bool => $value !== '');

        return self::fromArray($projectDir, array_merge(DotEnv::parse($projectDir . $file), $environment));
    }

    /**
     * @param array<string, string> $values
     */
    public static function fromArray(string $projectDir, array $values): self
    {
        $databasePath = $values['DATABASE_PATH'] ?? 'storage/database.sqlite';

        if ($databasePath !== ':memory:' && !str_starts_with($databasePath, '/')) {
            $databasePath = $projectDir . '/' . $databasePath;
        }

        return new self(
            projectDir: $projectDir,
            checkoApiKey: $values['CHECKO_API_KEY'] ?? '',
            checkoBaseUrl: rtrim($values['CHECKO_BASE_URL'] ?? 'https://api.checko.ru/v2', '/'),
            checkoTimeout: (float) ($values['CHECKO_TIMEOUT'] ?? 10),
            checkoDailyLimit: (int) ($values['CHECKO_DAILY_LIMIT'] ?? 100),
            databasePath: $databasePath,
            logStream: $values['LOG_STREAM'] ?? 'php://stderr',
            demoToken: $values['API_DEMO_TOKEN'] ?? '',
            tokenDailyLimit: (int) ($values['API_TOKEN_DAILY_LIMIT'] ?? 1000),
        );
    }

    public function isDemoMode(): bool
    {
        return $this->checkoApiKey === '';
    }
}
