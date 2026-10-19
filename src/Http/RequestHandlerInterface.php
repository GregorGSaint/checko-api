<?php

declare(strict_types=1);

namespace CheckoApi\Http;

interface RequestHandlerInterface
{
    public function handle(Request $request): Response;
}
