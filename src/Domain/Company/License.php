<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use JsonSerializable;

final readonly class License implements JsonSerializable
{
    /**
     * @param list<string> $activities
     */
    public function __construct(
        public ?string $number,
        public ?string $issuedAt,
        public ?string $validFrom,
        public ?string $validUntil,
        public ?string $issuer,
        public array $activities,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'number' => $this->number,
            'issued_at' => $this->issuedAt,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'issuer' => $this->issuer,
            'activities' => $this->activities,
        ];
    }
}
