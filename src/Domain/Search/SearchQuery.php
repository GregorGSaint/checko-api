<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Search;

use CheckoApi\Domain\Exception\ValidationException;
use CheckoApi\Domain\Shared\PageRequest;

final readonly class SearchQuery
{
    public const DEFAULT_LIMIT = 20;

    public function __construct(
        public SearchBy $by,
        public SearchTarget $target,
        public string $query,
        public ?string $region = null,
        public bool $activeOnly = false,
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public static function fromParameters(array $parameters): self
    {
        $by = SearchBy::tryFrom($parameters['by'] ?? '') ?? throw ValidationException::invalidParameter(
            'by',
            'Параметр by обязателен, допустимые значения: ' . implode(', ', SearchBy::values()),
        );

        $target = SearchTarget::tryFrom($parameters['obj'] ?? SearchTarget::Organization->value)
            ?? throw ValidationException::invalidParameter('obj', 'Параметр obj: org — организации, ent — ИП');

        if ($target === SearchTarget::Entrepreneur && $by->requiresOrganizations()) {
            throw ValidationException::invalidParameter(
                'obj',
                sprintf('Поиск by=%s доступен только для организаций (obj=org)', $by->value),
            );
        }

        $region = $parameters['region'] ?? null;

        if ($region !== null && preg_match('/^\d{2}$/', $region) !== 1) {
            throw ValidationException::invalidParameter('region', 'Код региона — две цифры, например 77');
        }

        $page = PageRequest::fromParameters($parameters, self::DEFAULT_LIMIT);

        return new self(
            by: $by,
            target: $target,
            query: $by->normalize($parameters['query'] ?? ''),
            region: $region,
            activeOnly: in_array($parameters['active'] ?? '', ['true', '1'], true),
            page: $page->page,
            limit: $page->limit,
        );
    }
}
