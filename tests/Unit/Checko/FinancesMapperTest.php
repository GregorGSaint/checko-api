<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Checko;

use CheckoApi\Checko\Mapping\FinancesMapper;
use CheckoApi\Domain\Finance\FinancialStatements;
use CheckoApi\Domain\Finance\FinancialYear;
use CheckoApi\Domain\Finance\ReportLine;
use CheckoApi\Tests\Support\Fixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FinancesMapperTest extends TestCase
{
    private FinancialStatements $yandex;

    protected function setUp(): void
    {
        $this->yandex = (new FinancesMapper())->map(Fixture::json('finances.ok.yandex.json'));
    }

    public function testMapsAllYearsWithLatestFirst(): void
    {
        $years = $this->yandex->jsonSerialize()['years'];

        self::assertIsArray($years);
        self::assertCount(14, $years);
        self::assertSame(2024, $years[0]['year']);
        self::assertSame(2011, $years[13]['year']);
    }

    public function testTranslatesLineCodesIntoNamedIndicators(): void
    {
        $year = $this->year(2024);

        self::assertSame(544_581_317_000, $year->value(ReportLine::Revenue));
        self::assertSame(-11_681_168_000, $year->value(ReportLine::NetProfit));
        self::assertSame(565_464_722_000, $year->value(ReportLine::TotalAssets));
        self::assertSame('https://bo.nalog.gov.ru/download/bfo/pdf/52634181', $year->reportUrl);
    }

    public function testBalanceSheetAddsUp(): void
    {
        $year = $this->year(2024);

        self::assertSame(
            $year->value(ReportLine::TotalAssets),
            (int) $year->value(ReportLine::NonCurrentAssets) + (int) $year->value(ReportLine::CurrentAssets),
        );
        self::assertSame(
            $year->value(ReportLine::TotalEquityAndLiabilities),
            (int) $year->value(ReportLine::Equity) + (int) $year->value(ReportLine::LongTermLiabilities) + (int) $year->value(ReportLine::ShortTermLiabilities),
        );
    }

    #[DataProvider('expectedMetrics')]
    public function testDerivedMetrics(int $year, float $netMargin, ?float $revenueGrowth, float $debtRatio): void
    {
        $metrics = $this->yandex->metricsFor($this->year($year));

        self::assertSame($netMargin, $metrics->netMargin);
        self::assertSame($revenueGrowth, $metrics->revenueGrowth);
        self::assertSame($debtRatio, $metrics->debtRatio);
    }

    /**
     * @return iterable<string, array{int, float, ?float, float}>
     */
    public static function expectedMetrics(): iterable
    {
        yield 'убыток 2024' => [2024, -0.0214, 0.2905, 0.7714];
        yield 'глубокий убыток 2023' => [2023, -0.1385, 0.3974, 0.7215];
        yield 'выручка растёт, прибыль падает, 2021' => [2021, 0.0597, 0.5745, 0.2944];
        yield 'первый год без базы для роста' => [2011, 0.308, null, 0.1906];
    }

    public function testDetailedLinesMissingAfter2019DoNotBreakMapping(): void
    {
        self::assertNotNull($this->year(2018)->value(ReportLine::Revenue));
        self::assertNotNull($this->year(2019)->value(ReportLine::Revenue));
    }

    public function testBankWithoutStatementsIsExplicitlyEmpty(): void
    {
        $bank = (new FinancesMapper())->map(Fixture::json('finances.empty.bank.json'));

        self::assertFalse($bank->hasStatements());
        self::assertSame('ПАО СБЕРБАНК', $bank->name);
        self::assertSame([], $bank->jsonSerialize()['years']);
    }

    public function testMissingLineGivesNullMetricInsteadOfZero(): void
    {
        $statements = new FinancialStatements(null, null, null, [
            new FinancialYear(2024, ['net_profit' => 100]),
        ]);

        $metrics = $statements->metricsFor(new FinancialYear(2024, ['net_profit' => 100]));

        self::assertNull($metrics->netMargin);
        self::assertNull($metrics->debtRatio);
    }

    private function year(int $year): FinancialYear
    {
        return $this->yandex->year($year) ?? self::fail('Нет данных за ' . $year);
    }
}
