<?php

declare(strict_types=1);

namespace CheckoApi\Domain\Shared;

use JsonSerializable;

final readonly class RiskFlags implements JsonSerializable
{
    public function __construct(
        public bool $unreliableSupplier,
        public bool $massDirector,
        public bool $massFounder,
        public int $bankruptcyMessages,
        public ?bool $disqualifiedPersons = null,
        public ?bool $illegalFinance = null,
        public ?bool $sanctions = null,
        public ?bool $sanctionedFounders = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'unreliable_supplier' => $this->unreliableSupplier,
            'mass_director' => $this->massDirector,
            'mass_founder' => $this->massFounder,
            'bankruptcy_messages' => $this->bankruptcyMessages,
            'disqualified_persons' => $this->disqualifiedPersons,
            'illegal_finance' => $this->illegalFinance,
            'sanctions' => $this->sanctions,
            'sanctioned_founders' => $this->sanctionedFounders,
        ];
    }
}
