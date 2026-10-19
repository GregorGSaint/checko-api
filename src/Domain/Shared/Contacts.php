<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class Contacts implements JsonSerializable
{
    /**
     * @param list<string> $phones
     * @param list<string> $emails
     */
    public function __construct(
        public array $phones,
        public array $emails,
        public ?string $website,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['phones' => $this->phones, 'emails' => $this->emails, 'website' => $this->website];
    }
}
