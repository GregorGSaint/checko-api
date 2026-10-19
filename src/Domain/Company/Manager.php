<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use JsonSerializable;

final readonly class Manager implements JsonSerializable
{
    public function __construct(
        public ?string $name,
        public ?string $inn,
        public ?string $position,
        public ?string $since,
        public bool $accessRestricted,
        public bool $isUnreliable,
        public bool $isMassDirector,
        public bool $isDisqualified,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'inn' => $this->inn,
            'position' => $this->position,
            'since' => $this->since,
            'access_restricted' => $this->accessRestricted,
            'is_unreliable' => $this->isUnreliable,
            'is_mass_director' => $this->isMassDirector,
            'is_disqualified' => $this->isDisqualified,
        ];
    }
}
