<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use JsonSerializable;

final readonly class Capital implements JsonSerializable
{
    public function __construct(
        public ?string $type,
        public ?float $amount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['type' => $this->type, 'amount' => $this->amount];
    }
}
