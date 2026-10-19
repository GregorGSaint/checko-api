<?php

declare(strict_types=1);

namespace CheckoApi\Auth;

use CheckoApi\Support\ClockInterface;

final readonly class TokenService
{
    private const PREFIX = 'ck_';

    public function __construct(
        private TokenStoreInterface $store,
        private ClockInterface $clock,
    ) {}

    public function issue(string $name, int $dailyLimit): string
    {
        $token = self::PREFIX . bin2hex(random_bytes(24));
        $this->store->create($name, self::hash($token), $dailyLimit, $this->clock->now());

        return $token;
    }

    public function seed(string $name, string $token, int $dailyLimit): bool
    {
        if ($token === '' || $this->store->existsByHash(self::hash($token))) {
            return false;
        }

        $this->store->create($name, self::hash($token), $dailyLimit, $this->clock->now());

        return true;
    }

    public function authenticate(?string $authorization): ApiToken
    {
        if ($authorization === null || preg_match('/^Bearer\s+(\S+)$/i', trim($authorization), $matches) !== 1) {
            throw AuthenticationException::missing();
        }

        $token = $this->store->findActiveByHash(self::hash($matches[1])) ?? throw AuthenticationException::invalid();
        $this->store->markUsed($token->id, $this->clock->now());

        return $token;
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
