<?php

declare(strict_types=1);

namespace CheckoApi\Auth;

use RuntimeException;

final class AuthenticationException extends RuntimeException
{
    public static function missing(): self
    {
        return new self('Передайте токен в заголовке Authorization: Bearer <token>');
    }

    public static function invalid(): self
    {
        return new self('Токен не найден или отключён');
    }
}
