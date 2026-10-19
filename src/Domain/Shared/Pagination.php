<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class Pagination implements JsonSerializable
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?int $total,
        public int $pagesTotal,
    ) {}

    public function hasNext(): bool
    {
        return $this->page < $this->pagesTotal;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'pages_total' => $this->pagesTotal,
            'has_next' => $this->hasNext(),
        ];
    }
}
