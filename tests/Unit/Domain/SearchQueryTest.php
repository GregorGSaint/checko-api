<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Domain;

use CheckoApi\Domain\Exception\ValidationException;
use CheckoApi\Domain\Search\SearchBy;
use CheckoApi\Domain\Search\SearchQuery;
use CheckoApi\Domain\Search\SearchTarget;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SearchQueryTest extends TestCase
{
    public function testDefaults(): void
    {
        $query = SearchQuery::fromParameters(['by' => 'name', 'query' => '  Яндекс ']);

        self::assertSame(SearchBy::Name, $query->by);
        self::assertSame(SearchTarget::Organization, $query->target);
        self::assertSame('Яндекс', $query->query);
        self::assertSame(1, $query->page);
        self::assertSame(SearchQuery::DEFAULT_LIMIT, $query->limit);
    }

    public function testNormalizesPhone(): void
    {
        self::assertSame('+78005555777', SearchQuery::fromParameters(['by' => 'phone', 'query' => '+7 (800) 555-57-77'])->query);
    }

    /**
     * @param array<string, string> $parameters
     */
    #[DataProvider('invalidParameters')]
    public function testRejectsInvalidParameters(array $parameters, string $parameter): void
    {
        try {
            SearchQuery::fromParameters($parameters);
            self::fail('Ожидалась ошибка валидации');
        } catch (ValidationException $exception) {
            self::assertSame($parameter, $exception->parameter);
        }
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidParameters(): iterable
    {
        yield 'нет by' => [['query' => 'яндекс'], 'by'];
        yield 'неизвестный by' => [['by' => 'inn', 'query' => 'яндекс'], 'by'];
        yield 'нет query' => [['by' => 'name'], 'query'];
        yield 'короткое название' => [['by' => 'name', 'query' => 'abc'], 'query'];
        yield 'кириллица считается по символам' => [['by' => 'name', 'query' => 'Рог'], 'query'];
        yield 'кривой ОКВЭД' => [['by' => 'okved', 'query' => '6.2'], 'query'];
        yield 'несуществующая дата' => [['by' => 'reg-date', 'query' => '2026-02-30'], 'query'];
        yield 'телефон не российский' => [['by' => 'phone', 'query' => '+15551234567'], 'query'];
        yield 'email без домена' => [['by' => 'email', 'query' => 'info@'], 'query'];
        yield 'поиск по учредителю среди ИП' => [['by' => 'founder-name', 'obj' => 'ent', 'query' => 'Иванов Иван'], 'obj'];
        yield 'регион из трёх цифр' => [['by' => 'name', 'query' => 'яндекс', 'region' => '770'], 'region'];
        yield 'нулевая страница' => [['by' => 'name', 'query' => 'яндекс', 'page' => '0'], 'page'];
        yield 'слишком большой лимит' => [['by' => 'name', 'query' => 'яндекс', 'limit' => '101'], 'limit'];
    }
}
