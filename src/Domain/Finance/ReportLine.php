<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Finance;

enum ReportLine: string
{
    case NonCurrentAssets = '1100';
    case FixedAssets = '1150';
    case CurrentAssets = '1200';
    case Receivables = '1230';
    case Cash = '1250';
    case Equity = '1300';
    case LongTermLiabilities = '1400';
    case ShortTermLiabilities = '1500';
    case TotalAssets = '1600';
    case TotalEquityAndLiabilities = '1700';
    case GrossProfit = '2100';
    case Revenue = '2110';
    case CostOfSales = '2120';
    case SalesProfit = '2200';
    case ProfitBeforeTax = '2300';
    case NetProfit = '2400';

    public function key(): string
    {
        return match ($this) {
            self::NonCurrentAssets => 'non_current_assets',
            self::FixedAssets => 'fixed_assets',
            self::CurrentAssets => 'current_assets',
            self::Receivables => 'receivables',
            self::Cash => 'cash',
            self::Equity => 'equity',
            self::LongTermLiabilities => 'long_term_liabilities',
            self::ShortTermLiabilities => 'short_term_liabilities',
            self::TotalAssets => 'total_assets',
            self::TotalEquityAndLiabilities => 'total_equity_and_liabilities',
            self::GrossProfit => 'gross_profit',
            self::Revenue => 'revenue',
            self::CostOfSales => 'cost_of_sales',
            self::SalesProfit => 'sales_profit',
            self::ProfitBeforeTax => 'profit_before_tax',
            self::NetProfit => 'net_profit',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::NonCurrentAssets => 'Внеоборотные активы',
            self::FixedAssets => 'Основные средства',
            self::CurrentAssets => 'Оборотные активы',
            self::Receivables => 'Дебиторская задолженность',
            self::Cash => 'Денежные средства и эквиваленты',
            self::Equity => 'Капитал и резервы',
            self::LongTermLiabilities => 'Долгосрочные обязательства',
            self::ShortTermLiabilities => 'Краткосрочные обязательства',
            self::TotalAssets => 'Баланс (актив)',
            self::TotalEquityAndLiabilities => 'Баланс (пассив)',
            self::GrossProfit => 'Валовая прибыль (убыток)',
            self::Revenue => 'Выручка',
            self::CostOfSales => 'Себестоимость продаж',
            self::SalesProfit => 'Прибыль (убыток) от продаж',
            self::ProfitBeforeTax => 'Прибыль (убыток) до налогообложения',
            self::NetProfit => 'Чистая прибыль (убыток)',
        };
    }
}
