<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Exception;

use InvalidArgumentException;

final class ValidationException extends InvalidArgumentException
{
    private function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly string $parameter,
    ) {
        parent::__construct($message);
    }

    public static function invalidIdentifier(string $message): self
    {
        return new self($message, 'INVALID_IDENTIFIER', 'id');
    }

    public static function wrongResource(string $message): self
    {
        return new self($message, 'WRONG_RESOURCE', 'id');
    }

    public static function invalidParameter(string $parameter, string $message): self
    {
        return new self($message, 'INVALID_PARAMETER', $parameter);
    }
}
