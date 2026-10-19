<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Mapping;

use CheckoApi\Domain\Search\CompanyHit;
use CheckoApi\Domain\Search\EntrepreneurHit;
use CheckoApi\Domain\Search\SearchQuery;
use CheckoApi\Domain\Search\SearchTarget;
use CheckoApi\Domain\Shared\Page;
use CheckoApi\Domain\Shared\Pagination;

final class SearchMapper
{
    public function map(Row $data, SearchQuery $query): Page
    {
        $records = $data->rows('Записи');

        $items = $query->target === SearchTarget::Organization
            ? array_map($this->company(...), $records)
            : array_map($this->entrepreneur(...), $records);

        return new Page($items, new Pagination(
            page: $data->int('СтрТекущ') ?? $query->page,
            perPage: $query->limit,
            total: $data->int('ЗапВсего'),
            pagesTotal: $data->int('СтрВсего') ?? 0,
        ));
    }

    private function company(Row $row): CompanyHit
    {
        return new CompanyHit(
            ogrn: $row->requiredString('ОГРН'),
            inn: $row->requiredString('ИНН'),
            kpp: $row->string('КПП'),
            shortName: $row->string('НаимСокр'),
            fullName: $row->string('НаимПолн'),
            registeredAt: $row->string('ДатаРег'),
            status: $row->string('Статус'),
            liquidatedAt: $row->string('ДатаЛикв'),
            regionCode: $row->string('РегионКод'),
            address: $row->string('ЮрАдрес'),
            activity: $row->string('ОКВЭД'),
        );
    }

    private function entrepreneur(Row $row): EntrepreneurHit
    {
        return new EntrepreneurHit(
            ogrnip: $row->requiredString('ОГРНИП'),
            inn: $row->requiredString('ИНН'),
            fullName: $row->string('ФИО'),
            type: $row->string('Тип'),
            registeredAt: $row->string('ДатаРег'),
            status: $row->string('Статус'),
            terminatedAt: $row->string('ДатаПрекращ'),
            regionCode: $row->string('РегионКод'),
            activity: $row->string('ОКВЭД'),
        );
    }
}
