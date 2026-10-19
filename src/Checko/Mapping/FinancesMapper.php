<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Mapping;

use CheckoApi\Domain\Finance\FinancialStatements;
use CheckoApi\Domain\Finance\FinancialYear;
use CheckoApi\Domain\Finance\ReportLine;

final class FinancesMapper
{
    /**
     * @param array<mixed> $body
     */
    public function map(array $body): FinancialStatements
    {
        $company = Row::of($body['company'] ?? null);
        $data = Row::of($body['data'] ?? null);
        $reports = Row::of($body['bo.nalog.ru'] ?? null)->row('Отчет');
        $years = [];

        foreach ($data->keys() as $year) {
            if (!ctype_digit($year)) {
                continue;
            }

            $years[] = new FinancialYear((int) $year, $this->lineValues($data->row($year)), $reports->string($year));
        }

        return new FinancialStatements(
            $company->string('ОГРН'),
            $company->string('ИНН'),
            $company->string('НаимСокр') ?? $company->string('НаимПолн'),
            $years,
        );
    }

    /**
     * @return array<string, int>
     */
    private function lineValues(Row $year): array
    {
        $values = [];

        foreach (ReportLine::cases() as $line) {
            $value = $year->int($line->value);

            if ($value !== null) {
                $values[$line->key()] = $value;
            }
        }

        return $values;
    }
}
