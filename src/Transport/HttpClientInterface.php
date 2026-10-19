<?php

declare(strict_types=1);

namespace CheckoApi\Transport;

interface HttpClientInterface
{
    /**
     * @throws TransportException
     */
    public function send(HttpRequest $request): HttpResponse;
}
