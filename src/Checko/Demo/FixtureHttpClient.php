<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Demo;

use CheckoApi\Transport\HttpClientInterface;
use CheckoApi\Transport\HttpRequest;
use CheckoApi\Transport\HttpResponse;
use CheckoApi\Transport\TransportException;

final readonly class FixtureHttpClient implements HttpClientInterface
{
    private const RECORDED = [
        'company' => [
            '7707083893' => 'company.ok.full.json',
            '1027700132195' => 'company.ok.full.json',
            '7736207543' => 'company.ok.restricted.json',
            '1027700229193' => 'company.ok.restricted.json',
        ],
        'entrepreneur' => [
            '402907671076' => 'entrepreneur.ok.json',
            '310402910200035' => 'entrepreneur.ok.json',
        ],
        'finances' => [
            '7736207543' => 'finances.ok.yandex.json',
            '1027700229193' => 'finances.ok.yandex.json',
            '7707083893' => 'finances.empty.bank.json',
            '1027700132195' => 'finances.empty.bank.json',
        ],
    ];

    private const NOT_FOUND = [
        'company' => 'company.not_found.json',
        'entrepreneur' => 'entrepreneur.not_found.json',
        'finances' => 'finances.not_found.json',
    ];

    private const SEARCH = [
        'org' => 'search.ok.org.json',
        'ent' => 'search.ok.ent.json',
    ];

    public function __construct(private string $directory) {}

    public function send(HttpRequest $request): HttpResponse
    {
        $endpoint = basename((string) parse_url($request->url, PHP_URL_PATH));
        $path = $this->directory . '/' . $this->resolve($endpoint, $request->query);

        if (!is_file($path)) {
            throw new TransportException('Демо-ответ не найден: ' . basename($path));
        }

        return new HttpResponse(200, (string) file_get_contents($path));
    }

    /**
     * @param array<string, string> $query
     */
    private function resolve(string $endpoint, array $query): string
    {
        if ($endpoint === 'search') {
            return self::SEARCH[$query['obj'] ?? 'org'] ?? self::SEARCH['org'];
        }

        $id = $query['inn'] ?? $query['ogrn'] ?? '';

        return self::RECORDED[$endpoint][$id] ?? self::NOT_FOUND[$endpoint] ?? self::NOT_FOUND['company'];
    }

    /**
     * @return list<string>
     */
    public static function knownIdentifiers(string $endpoint): array
    {
        return array_map(strval(...), array_keys(self::RECORDED[$endpoint] ?? []));
    }
}
