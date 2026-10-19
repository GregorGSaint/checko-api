<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Exception;

abstract class TemporaryFailureException extends CheckoException
{
    public function __construct(string $message, public readonly ?int $retryAfter = null)
    {
        parent::__construct($message);
    }
}
