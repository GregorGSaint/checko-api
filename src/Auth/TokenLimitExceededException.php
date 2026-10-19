<?php

declare(strict_types=1);

namespace CheckoApi\Auth;

use RuntimeException;

final class TokenLimitExceededException extends RuntimeException
{
    public function __construct(public readonly int $dailyLimit, public readonly int $retryAfter)
    {
        parent::__construct(sprintf('Суточный лимит токена исчерпан: %d запросов', $dailyLimit));
    }
}
