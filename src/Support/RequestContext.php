<?php

declare(strict_types=1);

namespace CheckoApi\Support;

final class RequestContext
{
    private ?int $tokenId = null;

    public function __construct(public readonly string $requestId) {}

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(8)));
    }

    public function authenticate(int $tokenId): void
    {
        $this->tokenId = $tokenId;
    }

    public function tokenId(): ?int
    {
        return $this->tokenId;
    }
}
