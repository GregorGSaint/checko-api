<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class Activity implements JsonSerializable
{
    public function __construct(
        public string $code,
        public ?string $name,
        public ?string $version,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'name' => $this->name, 'version' => $this->version];
    }
}
