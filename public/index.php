<?php

declare(strict_types=1);

use CheckoApi\Bootstrap\Services;
use CheckoApi\Http\Request;

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url(is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);

    if (is_dir($file) && is_file($file . '/index.html')) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($file . '/index.html');

        return true;
    }

    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    Services::fromProjectDir(dirname(__DIR__))->api()->handle(Request::fromGlobals())->send();
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    header('Content-Type: application/problem+json; charset=utf-8');
    echo '{"type":"about:blank","title":"Внутренняя ошибка","status":500,"detail":"Сервис не смог обработать запрос","code":"INTERNAL_ERROR"}';
}
