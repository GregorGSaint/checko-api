<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class Taxes implements JsonSerializable
{
    /**
     * @param list<string> $specialRegimes
     */
    public function __construct(
        public array $specialRegimes,
        public ?float $paidTotal,
        public ?string $paidYear,
        public ?float $arrears,
        public ?string $arrearsDate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'special_regimes' => $this->specialRegimes,
            'paid_total' => $this->paidTotal,
            'paid_year' => $this->paidYear,
            'arrears' => $this->arrears,
            'arrears_date' => $this->arrearsDate,
        ];
    }
}
