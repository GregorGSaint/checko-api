<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Checko;

use CheckoApi\Checko\Mapping\EntrepreneurMapper;
use CheckoApi\Checko\Mapping\Row;
use CheckoApi\Tests\Support\Fixture;
use PHPUnit\Framework\TestCase;

final class EntrepreneurMapperTest extends TestCase
{
    public function testMapsEntrepreneur(): void
    {
        $entrepreneur = (new EntrepreneurMapper())->map(Row::of(Fixture::json('entrepreneur.ok.json')['data'] ?? null));

        self::assertSame('310402910200035', $entrepreneur->ogrnip);
        self::assertSame('Морозов Андрей Сергеевич', $entrepreneur->fullName);
        self::assertTrue($entrepreneur->isActive());
        self::assertSame('Калужская область', $entrepreneur->region?->name);
        self::assertSame(['УСН'], $entrepreneur->taxes->specialRegimes);
        self::assertSame('МИКРОПРЕДПРИЯТИЕ', $entrepreneur->sme?->category);
        self::assertSame([], $entrepreneur->contacts->phones);
        self::assertNull($entrepreneur->risks->sanctions);
    }
}
