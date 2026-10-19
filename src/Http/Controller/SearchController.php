<?php

declare(strict_types=1);

namespace CheckoApi\Http\Controller;

use CheckoApi\Checko\CheckoClientInterface;
use CheckoApi\Checko\Endpoint;
use CheckoApi\Checko\Mapping\Row;
use CheckoApi\Checko\Mapping\SearchMapper;
use CheckoApi\Checko\Query;
use CheckoApi\Domain\Search\CompanyHit;
use CheckoApi\Domain\Search\EntrepreneurHit;
use CheckoApi\Domain\Search\SearchQuery;
use CheckoApi\Domain\Shared\Pagination;
use CheckoApi\Http\Request;
use CheckoApi\Http\Responder;
use CheckoApi\Http\Response;

final readonly class SearchController
{
    public function __construct(
        private CheckoClientInterface $checko,
        private SearchMapper $mapper,
        private Responder $responder,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public function search(Request $request, array $parameters): Response
    {
        $query = SearchQuery::fromParameters($request->query);
        $source = $this->checko->fetch(Endpoint::Search, Query::forSearch($query), $request->flag('refresh'));

        if ($request->flag('raw')) {
            return $this->responder->raw($source);
        }

        $page = $this->mapper->map(Row::of($source->data()), $query);
        $items = [];

        foreach ($page->items as $hit) {
            $items[] = match (true) {
                $hit instanceof CompanyHit => $hit->jsonSerialize() + ['links' => ['self' => '/v1/companies/' . $hit->inn]],
                $hit instanceof EntrepreneurHit => $hit->jsonSerialize() + ['links' => ['self' => '/v1/entrepreneurs/' . $hit->inn]],
                default => $hit,
            };
        }

        return $this->responder->resource($source, $items, $this->pageLinks($request, $page->pagination), $page->pagination);
    }

    /**
     * @return array<string, string>
     */
    private function pageLinks(Request $request, Pagination $pagination): array
    {
        $query = array_diff_key($request->query, ['raw' => true, 'refresh' => true]);
        $link = static fn(int $page): string => '/v1/search?' . http_build_query(['page' => $page] + $query);
        $links = ['self' => $link($pagination->page)];

        if ($pagination->hasNext()) {
            $links['next'] = $link($pagination->page + 1);
        }

        if ($pagination->page > 1) {
            $links['prev'] = $link($pagination->page - 1);
        }

        return $links;
    }
}
