<?php

declare(strict_types=1);

namespace CheckoApi\Http;

final readonly class Problem
{
    private const TITLES = [
        401 => 'Требуется авторизация',
        404 => 'Не найдено',
        405 => 'Метод не поддерживается',
        422 => 'Некорректный запрос',
        429 => 'Превышен лимит запросов',
        500 => 'Внутренняя ошибка',
        502 => 'Ошибка источника данных',
        503 => 'Источник временно недоступен',
        504 => 'Источник не ответил вовремя',
    ];

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        public int $status,
        public string $code,
        public string $detail,
        public array $headers = [],
        public array $extensions = [],
    ) {}

    public function title(): string
    {
        return self::TITLES[$this->status] ?? 'Ошибка';
    }

    public function toResponse(string $requestId): Response
    {
        $body = [
            'type' => 'about:blank',
            'title' => $this->title(),
            'status' => $this->status,
            'detail' => $this->detail,
            'code' => $this->code,
            'request_id' => $requestId,
        ] + $this->extensions;

        return Response::json($body, $this->status, $this->headers)
            ->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
    }
}
