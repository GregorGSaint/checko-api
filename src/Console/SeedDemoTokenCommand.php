<?php

declare(strict_types=1);

namespace CheckoApi\Console;

use CheckoApi\Auth\TokenService;

final readonly class SeedDemoTokenCommand implements CommandInterface
{
    public function __construct(
        private TokenService $tokens,
        private string $demoToken,
        private int $dailyLimit,
    ) {}

    public function name(): string
    {
        return 'token:seed-demo';
    }

    public function description(): string
    {
        return 'Записать демо-токен из API_DEMO_TOKEN, если его ещё нет';
    }

    public function run(array $arguments, Output $output): int
    {
        if ($this->demoToken === '') {
            $output->line('API_DEMO_TOKEN не задан, демо-токен не создаётся');

            return 0;
        }

        $output->line($this->tokens->seed('demo', $this->demoToken, $this->dailyLimit)
            ? 'Демо-токен записан'
            : 'Демо-токен уже есть');

        return 0;
    }
}
