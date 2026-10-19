<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Finance;

use JsonSerializable;

final readonly class FinancialMetrics implements JsonSerializable
{
    public function __construct(
        public ?float $netMargin,
        public ?float $revenueGrowth,
        public ?float $debtRatio,
    ) {}

    public static function calculate(FinancialYear $current, ?FinancialYear $previous): self
    {
        $liabilities = self::sum(
            $current->value(ReportLine::LongTermLiabilities),
            $current->value(ReportLine::ShortTermLiabilities),
        );

        return new self(
            netMargin: self::ratio($current->value(ReportLine::NetProfit), $current->value(ReportLine::Revenue)),
            revenueGrowth: self::growth($current->value(ReportLine::Revenue), $previous?->value(ReportLine::Revenue)),
            debtRatio: self::ratio($liabilities, $current->value(ReportLine::TotalAssets)),
        );
    }

    private static function ratio(?int $numerator, ?int $denominator): ?float
    {
        if ($numerator === null || $denominator === null || $denominator === 0) {
            return null;
        }

        return round($numerator / $denominator, 4);
    }

    private static function growth(?int $current, ?int $previous): ?float
    {
        if ($current === null || $previous === null || $previous <= 0) {
            return null;
        }

        return round(($current - $previous) / $previous, 4);
    }

    private static function sum(?int ...$values): ?int
    {
        return in_array(null, $values, true) ? null : array_sum($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'net_margin' => $this->netMargin,
            'revenue_growth' => $this->revenueGrowth,
            'debt_ratio' => $this->debtRatio,
        ];
    }
}
