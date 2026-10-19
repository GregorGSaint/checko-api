<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use JsonSerializable;

final readonly class Address implements JsonSerializable
{
    public function __construct(
        public ?string $full,
        public ?string $locality,
        public bool $isUnreliable,
        public int $massRegistrationCount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'full' => $this->full,
            'locality' => $this->locality,
            'is_unreliable' => $this->isUnreliable,
            'mass_registration_count' => $this->massRegistrationCount,
        ];
    }
}
