<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Checko;

use CheckoApi\Checko\Mapping\Row;
use CheckoApi\Checko\Mapping\SearchMapper;
use CheckoApi\Domain\Search\CompanyHit;
use CheckoApi\Domain\Search\EntrepreneurHit;
use CheckoApi\Domain\Search\SearchBy;
use CheckoApi\Domain\Search\SearchQuery;
use CheckoApi\Domain\Search\SearchTarget;
use CheckoApi\Tests\Support\Fixture;
use PHPUnit\Framework\TestCase;

final class SearchMapperTest extends TestCase
{
    public function testMapsOrganizationsWithSourcePagination(): void
    {
        $page = (new SearchMapper())->map(
            Row::of(Fixture::json('search.ok.org.json')['data'] ?? null),
            new SearchQuery(SearchBy::Name, SearchTarget::Organization, 'яндекс', limit: 3),
        );

        self::assertCount(3, $page->items);
        self::assertInstanceOf(CompanyHit::class, $page->items[0]);
        self::assertSame('7736207543', $page->items[0]->inn);
        self::assertSame(53, $page->pagination->total);
        self::assertSame(18, $page->pagination->pagesTotal);
        self::assertTrue($page->pagination->hasNext());
    }

    public function testMapsEntrepreneurs(): void
    {
        $page = (new SearchMapper())->map(
            Row::of(Fixture::json('search.ok.ent.json')['data'] ?? null),
            new SearchQuery(SearchBy::Name, SearchTarget::Entrepreneur, 'морозов андрей', limit: 3),
        );

        self::assertInstanceOf(EntrepreneurHit::class, $page->items[0]);
        self::assertSame('310402910200035', $page->items[0]->ogrnip);
        self::assertSame(1000, $page->pagination->total);
    }
}
