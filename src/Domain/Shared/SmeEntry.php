<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class SmeEntry implements JsonSerializable
{
    public function __construct(
        public string $category,
        public ?string $includedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['category' => $this->category, 'included_at' => $this->includedAt];
    }
}
