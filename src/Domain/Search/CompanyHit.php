<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Search;

use JsonSerializable;

final readonly class CompanyHit implements JsonSerializable
{
    public function __construct(
        public string $ogrn,
        public string $inn,
        public ?string $kpp,
        public ?string $shortName,
        public ?string $fullName,
        public ?string $registeredAt,
        public ?string $status,
        public ?string $liquidatedAt,
        public ?string $regionCode,
        public ?string $address,
        public ?string $activity,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => 'company',
            'ogrn' => $this->ogrn,
            'inn' => $this->inn,
            'kpp' => $this->kpp,
            'name' => ['short' => $this->shortName, 'full' => $this->fullName],
            'registered_at' => $this->registeredAt,
            'is_active' => $this->liquidatedAt === null,
            'status' => $this->status,
            'liquidated_at' => $this->liquidatedAt,
            'region_code' => $this->regionCode,
            'address' => $this->address,
            'activity' => $this->activity,
        ];
    }
}
