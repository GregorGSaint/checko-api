<?php

declare(strict_types=1);

namespace CheckoApi\Checko;

use CheckoApi\Domain\Identifier\IdentifierKind;
use CheckoApi\Domain\Identifier\RegistryId;
use CheckoApi\Domain\Search\SearchQuery;

final class Query
{
    /**
     * @return array<string, string>
     */
    public static function forIdentifier(RegistryId $id): array
    {
        return [$id->kind === IdentifierKind::Inn ? 'inn' : 'ogrn' => $id->value];
    }

    /**
     * @return array<string, string>
     */
    public static function forSearch(SearchQuery $search): array
    {
        return array_filter([
            'by' => $search->by->value,
            'obj' => $search->target->value,
            'query' => $search->query,
            'region' => $search->region ?? '',
            'active' => $search->activeOnly ? 'true' : '',
            'page' => (string) $search->page,
            'limit' => (string) $search->limit,
        ], static fn(string $value): bool => $value !== '');
    }
}
