<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Finance;

use JsonSerializable;

final readonly class FinancialStatements implements JsonSerializable
{
    /** @var array<int, FinancialYear> */
    private array $byYear;

    /**
     * @param list<FinancialYear> $years
     */
    public function __construct(
        public ?string $ogrn,
        public ?string $inn,
        public ?string $name,
        array $years,
    ) {
        $byYear = [];

        foreach ($years as $year) {
            $byYear[$year->year] = $year;
        }

        krsort($byYear);
        $this->byYear = $byYear;
    }

    public function hasStatements(): bool
    {
        return $this->byYear !== [];
    }

    public function year(int $year): ?FinancialYear
    {
        return $this->byYear[$year] ?? null;
    }

    public function metricsFor(FinancialYear $year): FinancialMetrics
    {
        return FinancialMetrics::calculate($year, $this->year($year->year - 1));
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'company' => ['ogrn' => $this->ogrn, 'inn' => $this->inn, 'name' => $this->name],
            'has_statements' => $this->hasStatements(),
            'currency' => 'RUB',
            'years' => array_values(array_map(
                fn(FinancialYear $year): array => $year->jsonSerialize() + ['metrics' => $this->metricsFor($year)],
                $this->byYear,
            )),
        ];
    }
}
