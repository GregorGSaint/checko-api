<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class Page
{
    /**
     * @param list<JsonSerializable> $items
     */
    public function __construct(
        public array $items,
        public Pagination $pagination,
    ) {}

    /**
     * @param list<JsonSerializable> $items
     */
    public static function slice(array $items, int $page, int $perPage): self
    {
        $total = count($items);

        return new self(
            array_slice($items, ($page - 1) * $perPage, $perPage),
            new Pagination($page, $perPage, $total, max(1, (int) ceil($total / $perPage))),
        );
    }
}
