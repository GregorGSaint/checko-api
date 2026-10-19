<?php

declare(strict_types=1);

namespace CheckoApi\Http\Controller;

use CheckoApi\Checko\CheckoClientInterface;
use CheckoApi\Checko\CheckoResponse;
use CheckoApi\Checko\Endpoint;
use CheckoApi\Checko\Mapping\CompanyMapper;
use CheckoApi\Checko\Mapping\FinancesMapper;
use CheckoApi\Checko\Mapping\Row;
use CheckoApi\Checko\Query;
use CheckoApi\Domain\Company\Company;
use CheckoApi\Domain\Identifier\RegistryId;
use CheckoApi\Domain\Shared\Page;
use CheckoApi\Domain\Shared\PageRequest;
use CheckoApi\Http\Request;
use CheckoApi\Http\Responder;
use CheckoApi\Http\Response;
use Closure;
use JsonSerializable;

final readonly class CompanyController
{
    private const COLLECTION_PAGE_SIZE = 50;

    public function __construct(
        private CheckoClientInterface $checko,
        private CompanyMapper $companies,
        private FinancesMapper $finances,
        private Responder $responder,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public function show(Request $request, array $parameters): Response
    {
        $id = RegistryId::forCompany($parameters['id'] ?? '');
        $source = $this->fetch(Endpoint::Company, $id, $request);

        if ($request->flag('raw')) {
            return $this->responder->raw($source);
        }

        $company = $this->companies->map(Row::of($source->data()));

        return $this->responder->resource($source, $company, $this->links($id, $company));
    }

    /**
     * @param array<string, string> $parameters
     */
    public function finances(Request $request, array $parameters): Response
    {
        $id = RegistryId::forCompany($parameters['id'] ?? '');
        $source = $this->fetch(Endpoint::Finances, $id, $request);

        if ($request->flag('raw')) {
            return $this->responder->raw($source);
        }

        return $this->responder->resource($source, $this->finances->map($source->body), ['company' => self::path($id)]);
    }

    /**
     * @param array<string, string> $parameters
     */
    public function trademarks(Request $request, array $parameters): Response
    {
        return $this->collection($request, $parameters, $this->companies->trademarks(...));
    }

    /**
     * @param array<string, string> $parameters
     */
    public function licenses(Request $request, array $parameters): Response
    {
        return $this->collection($request, $parameters, $this->companies->licenses(...));
    }

    /**
     * @param array<string, string> $parameters
     */
    public function branches(Request $request, array $parameters): Response
    {
        return $this->collection($request, $parameters, $this->companies->branches(...));
    }

    /**
     * @param array<string, string> $parameters
     */
    public function founders(Request $request, array $parameters): Response
    {
        return $this->collection($request, $parameters, $this->companies->founders(...));
    }

    /**
     * @param array<string, string> $parameters
     * @param Closure(Row): list<JsonSerializable> $extract
     */
    private function collection(Request $request, array $parameters, Closure $extract): Response
    {
        $id = RegistryId::forCompany($parameters['id'] ?? '');
        $pageRequest = PageRequest::fromParameters($request->query, self::COLLECTION_PAGE_SIZE);
        $source = $this->fetch(Endpoint::Company, $id, $request);
        $page = Page::slice($extract(Row::of($source->data())), $pageRequest->page, $pageRequest->limit);

        return $this->responder->resource($source, $page->items, ['company' => self::path($id)], $page->pagination);
    }

    private function fetch(Endpoint $endpoint, RegistryId $id, Request $request): CheckoResponse
    {
        return $this->checko->fetch($endpoint, Query::forIdentifier($id), $request->flag('refresh'));
    }

    /**
     * @return array<string, string>
     */
    private function links(RegistryId $id, Company $company): array
    {
        $self = self::path($id);
        $links = ['self' => $self, 'finances' => $self . '/finances'];

        foreach (array_keys($company->collectionSizes) as $collection) {
            $links[$collection] = $self . '/' . $collection;
        }

        return $links;
    }

    private static function path(RegistryId $id): string
    {
        return '/v1/companies/' . $id->value;
    }
}
