<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use JsonSerializable;

final readonly class Branch implements JsonSerializable
{
    public function __construct(
        public BranchKind $kind,
        public ?string $name,
        public ?string $kpp,
        public ?string $address,
        public ?string $country,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'kind' => $this->kind->value,
            'name' => $this->name,
            'kpp' => $this->kpp,
            'address' => $this->address,
            'country' => $this->country,
        ];
    }
}
