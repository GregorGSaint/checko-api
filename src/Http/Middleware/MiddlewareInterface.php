<?php

declare(strict_types=1);

namespace CheckoApi\Http\Middleware;

use CheckoApi\Http\Request;
use CheckoApi\Http\RequestHandlerInterface;
use CheckoApi\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response;
}
