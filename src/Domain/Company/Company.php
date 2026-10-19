<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use CheckoApi\Domain\Shared\Activity;
use CheckoApi\Domain\Shared\Classifier;
use CheckoApi\Domain\Shared\Contacts;
use CheckoApi\Domain\Shared\RiskFlags;
use CheckoApi\Domain\Shared\SmeEntry;
use CheckoApi\Domain\Shared\Status;
use CheckoApi\Domain\Shared\Taxes;
use CheckoApi\Domain\Shared\Termination;
use JsonSerializable;

final readonly class Company implements JsonSerializable
{
    /**
     * @param list<Activity> $additionalActivities
     * @param list<Manager> $managers
     * @param array<string, int> $collectionSizes
     */
    public function __construct(
        public string $ogrn,
        public string $inn,
        public ?string $kpp,
        public ?string $okpo,
        public ?string $shortName,
        public ?string $fullName,
        public ?string $englishName,
        public ?string $registeredAt,
        public Status $status,
        public ?Termination $liquidation,
        public ?Classifier $region,
        public ?Address $address,
        public ?Activity $mainActivity,
        public array $additionalActivities,
        public ?Classifier $legalForm,
        public ?Capital $capital,
        public array $managers,
        public ?OrganizationRef $managingOrganization,
        public Contacts $contacts,
        public Taxes $taxes,
        public ?SmeEntry $sme,
        public RiskFlags $risks,
        public array $collectionSizes,
        public ?string $extractedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->liquidation === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'ogrn' => $this->ogrn,
            'inn' => $this->inn,
            'kpp' => $this->kpp,
            'okpo' => $this->okpo,
            'name' => ['short' => $this->shortName, 'full' => $this->fullName, 'english' => $this->englishName],
            'registered_at' => $this->registeredAt,
            'is_active' => $this->isActive(),
            'status' => $this->status,
            'liquidation' => $this->liquidation,
            'region' => $this->region,
            'address' => $this->address,
            'main_activity' => $this->mainActivity,
            'additional_activities' => $this->additionalActivities,
            'legal_form' => $this->legalForm,
            'authorized_capital' => $this->capital,
            'managers' => $this->managers,
            'managing_organization' => $this->managingOrganization,
            'contacts' => $this->contacts,
            'taxes' => $this->taxes,
            'sme' => $this->sme,
            'risks' => $this->risks,
            'collections' => $this->collectionSizes,
            'extracted_at' => $this->extractedAt,
        ];
    }
}
