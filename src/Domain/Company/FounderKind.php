<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

enum FounderKind: string
{
    case Person = 'person';
    case RussianCompany = 'russian_company';
    case ForeignCompany = 'foreign_company';
    case MutualFund = 'mutual_fund';
    case State = 'state';
}
