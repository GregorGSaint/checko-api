<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Entrepreneur;

use CheckoApi\Domain\Shared\Activity;
use CheckoApi\Domain\Shared\Classifier;
use CheckoApi\Domain\Shared\Contacts;
use CheckoApi\Domain\Shared\RiskFlags;
use CheckoApi\Domain\Shared\SmeEntry;
use CheckoApi\Domain\Shared\Status;
use CheckoApi\Domain\Shared\Taxes;
use CheckoApi\Domain\Shared\Termination;
use JsonSerializable;

final readonly class Entrepreneur implements JsonSerializable
{
    /**
     * @param list<Activity> $additionalActivities
     * @param array<string, int> $collectionSizes
     */
    public function __construct(
        public string $ogrnip,
        public string $inn,
        public ?string $okpo,
        public ?string $fullName,
        public ?string $type,
        public ?string $registeredAt,
        public Status $status,
        public ?Termination $termination,
        public ?Classifier $region,
        public ?string $locality,
        public ?Activity $mainActivity,
        public array $additionalActivities,
        public Contacts $contacts,
        public Taxes $taxes,
        public ?SmeEntry $sme,
        public RiskFlags $risks,
        public array $collectionSizes,
        public ?string $extractedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->termination === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'ogrnip' => $this->ogrnip,
            'inn' => $this->inn,
            'okpo' => $this->okpo,
            'full_name' => $this->fullName,
            'type' => $this->type,
            'registered_at' => $this->registeredAt,
            'is_active' => $this->isActive(),
            'status' => $this->status,
            'termination' => $this->termination,
            'region' => $this->region,
            'locality' => $this->locality,
            'main_activity' => $this->mainActivity,
            'additional_activities' => $this->additionalActivities,
            'contacts' => $this->contacts,
            'taxes' => $this->taxes,
            'sme' => $this->sme,
            'risks' => $this->risks,
            'collections' => $this->collectionSizes,
            'extracted_at' => $this->extractedAt,
        ];
    }
}
