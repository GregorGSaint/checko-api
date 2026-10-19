<?php

declare(strict_types=1);

namespace CheckoApi\Http\Exception;

use RuntimeException;

final class MethodNotAllowedException extends RuntimeException
{
    /**
     * @param list<string> $allowed
     */
    public function __construct(public readonly array $allowed)
    {
        parent::__construct('Ресурс поддерживает методы: ' . implode(', ', $allowed));
    }
}
