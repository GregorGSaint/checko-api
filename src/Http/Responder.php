<?php

declare(strict_types=1);

namespace CheckoApi\Http;

use CheckoApi\Cache\CacheStatus;
use CheckoApi\Checko\CheckoResponse;
use CheckoApi\Domain\Shared\Pagination;

final readonly class Responder
{
    /**
     * @param array<string, string> $links
     */
    public function resource(CheckoResponse $source, mixed $data, array $links = [], ?Pagination $pagination = null): Response
    {
        $meta = [
            'cache' => $source->cacheStatus->value,
            'fetched_at' => $source->fetchedAt->format(DATE_ATOM),
        ];

        if ($pagination !== null) {
            $meta['pagination'] = $pagination;
        }

        $body = ['data' => $data] + ($links === [] ? [] : ['links' => $links]) + ['meta' => $meta];

        return Response::json($body, headers: $this->headers($source));
    }

    public function raw(CheckoResponse $source): Response
    {
        return Response::json($source->withoutAccountMeta(), headers: $this->headers($source));
    }

    /**
     * @return array<string, string>
     */
    private function headers(CheckoResponse $source): array
    {
        $headers = ['X-Cache' => $source->cacheStatus->value];

        if ($source->cacheStatus === CacheStatus::Stale) {
            $headers['Warning'] = '110 - "Response is Stale"';
        }

        return $headers;
    }
}
