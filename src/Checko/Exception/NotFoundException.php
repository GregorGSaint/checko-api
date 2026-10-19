<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Exception;

use CheckoApi\Checko\Endpoint;

final class NotFoundException extends CheckoException
{
    public function __construct(public readonly Endpoint $endpoint, ?string $message = null)
    {
        parent::__construct($message ?? 'По указанным реквизитам ничего не найдено');
    }
}
