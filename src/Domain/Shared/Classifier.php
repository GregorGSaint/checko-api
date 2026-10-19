<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class Classifier implements JsonSerializable
{
    public function __construct(
        public ?string $code,
        public ?string $name,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'name' => $this->name];
    }
}
