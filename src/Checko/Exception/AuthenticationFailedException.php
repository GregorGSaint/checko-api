<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Exception;

final class AuthenticationFailedException extends CheckoException
{
    public function __construct(?string $sourceMessage)
    {
        parent::__construct('Источник отклонил ключ API: ' . ($sourceMessage ?? 'без пояснения'));
    }
}
