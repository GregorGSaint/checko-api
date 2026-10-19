<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Exception;

use CheckoApi\Transport\TransportException;

final class UpstreamUnavailableException extends TemporaryFailureException
{
    private function __construct(string $message, public readonly bool $timedOut)
    {
        parent::__construct($message);
    }

    public static function fromTransport(TransportException $exception): self
    {
        return new self('Сетевая ошибка при обращении к источнику: ' . $exception->getMessage(), $exception->timedOut);
    }

    public static function fromStatus(int $status): self
    {
        return new self(sprintf('Источник ответил HTTP %d', $status), false);
    }
}
