<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Finance;

use JsonSerializable;

final readonly class FinancialYear implements JsonSerializable
{
    /**
     * @param array<string, int> $values
     */
    public function __construct(
        public int $year,
        private array $values,
        public ?string $reportUrl = null,
    ) {}

    public function value(ReportLine $line): ?int
    {
        return $this->values[$line->key()] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $indicators = [];

        foreach (ReportLine::cases() as $line) {
            $indicators[$line->key()] = $this->value($line);
        }

        return ['year' => $this->year, 'report_url' => $this->reportUrl, 'indicators' => $indicators];
    }
}
