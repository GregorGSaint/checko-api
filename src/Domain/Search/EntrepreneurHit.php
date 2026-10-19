<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Search;

use JsonSerializable;

final readonly class EntrepreneurHit implements JsonSerializable
{
    public function __construct(
        public string $ogrnip,
        public string $inn,
        public ?string $fullName,
        public ?string $type,
        public ?string $registeredAt,
        public ?string $status,
        public ?string $terminatedAt,
        public ?string $regionCode,
        public ?string $activity,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => 'entrepreneur',
            'ogrnip' => $this->ogrnip,
            'inn' => $this->inn,
            'full_name' => $this->fullName,
            'kind' => $this->type,
            'registered_at' => $this->registeredAt,
            'is_active' => $this->terminatedAt === null,
            'status' => $this->status,
            'terminated_at' => $this->terminatedAt,
            'region_code' => $this->regionCode,
            'activity' => $this->activity,
        ];
    }
}
