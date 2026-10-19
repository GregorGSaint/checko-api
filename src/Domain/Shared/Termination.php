<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class Termination implements JsonSerializable
{
    public function __construct(
        public ?string $date,
        public ?string $reason,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['date' => $this->date, 'reason' => $this->reason];
    }
}
