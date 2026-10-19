<?php

declare(strict_types=1);

namespace CheckoApi\Console;

use CheckoApi\Auth\TokenService;

final readonly class CreateTokenCommand implements CommandInterface
{
    public function __construct(
        private TokenService $tokens,
        private int $defaultDailyLimit,
    ) {}

    public function name(): string
    {
        return 'token:create';
    }

    public function description(): string
    {
        return 'Выпустить токен доступа: token:create <имя> [--limit=N]';
    }

    public function run(array $arguments, Output $output): int
    {
        $name = null;
        $limit = $this->defaultDailyLimit;

        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--limit=')) {
                $limit = (int) substr($argument, strlen('--limit='));
            } else {
                $name = $argument;
            }
        }

        if ($name === null || $limit < 1) {
            $output->error('Укажите имя токена и положительный лимит: token:create <имя> [--limit=N]');

            return 1;
        }

        $output->line($this->tokens->issue($name, $limit));
        $output->error(sprintf('Токен «%s» выпущен, лимит %d запросов в сутки. Сохраните его: повторно он не показывается.', $name, $limit));

        return 0;
    }
}
