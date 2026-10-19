<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use JsonSerializable;

final readonly class Founder implements JsonSerializable
{
    public function __construct(
        public FounderKind $kind,
        public ?string $name,
        public ?string $inn,
        public ?string $ogrn,
        public ?float $shareNominal,
        public ?float $sharePercent,
        public ?string $since,
        public bool $accessRestricted,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'kind' => $this->kind->value,
            'name' => $this->name,
            'inn' => $this->inn,
            'ogrn' => $this->ogrn,
            'share' => $this->shareNominal === null && $this->sharePercent === null
                ? null
                : ['nominal' => $this->shareNominal, 'percent' => $this->sharePercent],
            'since' => $this->since,
            'access_restricted' => $this->accessRestricted,
        ];
    }
}
