<?php

declare(strict_types=1);

namespace CheckoApi\Checko;

use JsonException;

final readonly class Meta
{
    public function __construct(
        public ?string $status = null,
        public ?string $message = null,
        public ?int $todayRequestCount = null,
        public ?float $balance = null,
    ) {}

    /**
     * @param array<mixed> $body
     */
    public static function fromBody(array $body): self
    {
        $meta = is_array($body['meta'] ?? null) ? $body['meta'] : [];

        return new self(
            status: is_string($meta['status'] ?? null) ? $meta['status'] : null,
            message: is_string($meta['message'] ?? null) ? $meta['message'] : null,
            todayRequestCount: is_int($meta['today_request_count'] ?? null) ? $meta['today_request_count'] : null,
            balance: is_int($meta['balance'] ?? null) || is_float($meta['balance'] ?? null) ? (float) $meta['balance'] : null,
        );
    }

    public static function fromJson(string $json): self
    {
        try {
            $body = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new self();
        }

        return is_array($body) ? self::fromBody($body) : new self();
    }

    public function isOk(): bool
    {
        return $this->status === 'ok';
    }
}
