<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Mapping;

use CheckoApi\Checko\Exception\InvalidResponseException;

final readonly class Row
{
    /**
     * @param array<mixed> $values
     */
    private function __construct(private array $values) {}

    public static function of(mixed $value): self
    {
        return new self(is_array($value) ? $value : []);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    public function string(string $key): ?string
    {
        $value = $this->values[$key] ?? null;

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    public function requiredString(string $key): string
    {
        return $this->string($key) ?? throw InvalidResponseException::missingField($key);
    }

    public function int(string $key): ?int
    {
        $value = $this->values[$key] ?? null;

        return match (true) {
            is_int($value) => $value,
            is_float($value) => (int) $value,
            is_string($value) && preg_match('/^-?\d+$/', $value) === 1 => (int) $value,
            default => null,
        };
    }

    public function float(string $key): ?float
    {
        $value = $this->values[$key] ?? null;

        return is_int($value) || is_float($value) ? (float) $value : null;
    }

    public function bool(string $key): bool
    {
        return ($this->values[$key] ?? null) === true;
    }

    public function row(string $key): self
    {
        return self::of($this->values[$key] ?? null);
    }

    /**
     * @return list<self>
     */
    public function rows(string $key): array
    {
        $value = $this->values[$key] ?? null;

        return is_array($value) ? array_values(array_map(self::of(...), array_filter($value, is_array(...)))) : [];
    }

    /**
     * @return list<string>
     */
    public function strings(string $key): array
    {
        $value = $this->values[$key] ?? null;

        if (!is_array($value)) {
            return [];
        }

        $strings = array_map(static fn(mixed $item): ?string => is_string($item) && trim($item) !== '' ? trim($item) : null, $value);

        return array_values(array_filter($strings, static fn(?string $item): bool => $item !== null));
    }

    public function count(string $key): int
    {
        $value = $this->values[$key] ?? null;

        return is_array($value) ? count($value) : 0;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(strval(...), array_keys($this->values));
    }
}
