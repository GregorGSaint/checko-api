<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Company;

use JsonSerializable;

final readonly class Trademark implements JsonSerializable
{
    public function __construct(
        public ?int $id,
        public ?string $url,
        public ?string $registeredAt,
        public ?string $expiresAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'registered_at' => $this->registeredAt,
            'expires_at' => $this->expiresAt,
        ];
    }
}
