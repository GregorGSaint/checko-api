<?php

declare(strict_types=1);

namespace CheckoApi\Checko;

use CheckoApi\Checko\Exception\CheckoException;

interface CheckoClientInterface
{
    /**
     * @param array<string, string> $query
     *
     * @throws CheckoException
     */
    public function fetch(Endpoint $endpoint, array $query, bool $bypassCache = false): CheckoResponse;
}
