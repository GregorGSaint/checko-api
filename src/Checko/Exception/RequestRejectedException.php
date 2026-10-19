<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Exception;

final class RequestRejectedException extends CheckoException
{
    public function __construct(?string $sourceMessage)
    {
        parent::__construct('Источник отклонил параметры запроса: ' . ($sourceMessage ?? 'без пояснения'));
    }
}
