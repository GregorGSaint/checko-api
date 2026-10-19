<?php

declare(strict_types=1);

namespace CheckoApi\Http\Exception;

use RuntimeException;

final class RouteNotFoundException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Ресурс %s не существует, список ресурсов — GET /v1', $path));
    }
}
