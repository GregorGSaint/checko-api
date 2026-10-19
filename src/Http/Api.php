<?php

declare(strict_types=1);

namespace CheckoApi\Http;

use CheckoApi\Http\Controller\CompanyController;
use CheckoApi\Http\Controller\EntrepreneurController;
use CheckoApi\Http\Controller\HealthController;
use CheckoApi\Http\Controller\SearchController;
use CheckoApi\Http\Middleware\MiddlewareInterface;
use CheckoApi\Http\Middleware\Pipeline;

final readonly class Api implements RequestHandlerInterface
{
    private Router $router;

    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        CompanyController $companies,
        EntrepreneurController $entrepreneurs,
        SearchController $search,
        HealthController $health,
        private array $middleware,
    ) {
        $this->router = (new Router())
            ->get('/v1', $this->index(...), 'Список ресурсов API')
            ->get('/v1/companies/{id}', $companies->show(...), 'Карточка организации по ИНН или ОГРН')
            ->get('/v1/companies/{id}/finances', $companies->finances(...), 'Бухгалтерская отчётность и расчётные показатели')
            ->get('/v1/companies/{id}/trademarks', $companies->trademarks(...), 'Товарные знаки организации')
            ->get('/v1/companies/{id}/licenses', $companies->licenses(...), 'Лицензии организации')
            ->get('/v1/companies/{id}/branches', $companies->branches(...), 'Филиалы и представительства')
            ->get('/v1/companies/{id}/founders', $companies->founders(...), 'Учредители организации')
            ->get('/v1/entrepreneurs/{id}', $entrepreneurs->show(...), 'Карточка ИП по ИНН или ОГРНИП')
            ->get('/v1/search', $search->search(...), 'Поиск организаций и ИП')
            ->get('/health', $health->show(...), 'Состояние сервиса и остаток квоты источника');
    }

    public function handle(Request $request): Response
    {
        return (new Pipeline($this->middleware, $this->router))->handle($request);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function index(Request $request, array $parameters): Response
    {
        $resources = array_map(static fn(Route $route): array => [
            'method' => $route->method,
            'path' => $route->pattern,
            'description' => $route->description,
        ], $this->router->routes());

        return Response::json([
            'data' => ['resources' => $resources],
            'links' => ['self' => '/v1', 'docs' => '/docs/', 'openapi' => '/openapi.yaml'],
        ]);
    }
}
