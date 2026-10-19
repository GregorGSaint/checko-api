<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use JsonSerializable;

final readonly class OrganizationRef implements JsonSerializable
{
    public function __construct(
        public ?string $ogrn,
        public ?string $inn,
        public ?string $name,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['ogrn' => $this->ogrn, 'inn' => $this->inn, 'name' => $this->name];
    }
}
