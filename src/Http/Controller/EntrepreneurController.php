<?php

declare(strict_types=1);

namespace CheckoApi\Http\Controller;

use CheckoApi\Checko\CheckoClientInterface;
use CheckoApi\Checko\Endpoint;
use CheckoApi\Checko\Mapping\EntrepreneurMapper;
use CheckoApi\Checko\Mapping\Row;
use CheckoApi\Checko\Query;
use CheckoApi\Domain\Identifier\RegistryId;
use CheckoApi\Http\Request;
use CheckoApi\Http\Responder;
use CheckoApi\Http\Response;

final readonly class EntrepreneurController
{
    public function __construct(
        private CheckoClientInterface $checko,
        private EntrepreneurMapper $mapper,
        private Responder $responder,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public function show(Request $request, array $parameters): Response
    {
        $id = RegistryId::forEntrepreneur($parameters['id'] ?? '');
        $source = $this->checko->fetch(Endpoint::Entrepreneur, Query::forIdentifier($id), $request->flag('refresh'));

        if ($request->flag('raw')) {
            return $this->responder->raw($source);
        }

        return $this->responder->resource(
            $source,
            $this->mapper->map(Row::of($source->data())),
            ['self' => '/v1/entrepreneurs/' . $id->value],
        );
    }
}
