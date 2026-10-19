<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

enum BranchKind: string
{
    case Branch = 'branch';
    case RepresentativeOffice = 'representative_office';
}
