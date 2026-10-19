<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class Status implements JsonSerializable
{
    public function __construct(
        public ?string $code,
        public ?string $name,
        public ?string $since = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'name' => $this->name, 'since' => $this->since];
    }
}
