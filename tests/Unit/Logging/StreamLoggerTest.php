<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Logging;

use CheckoApi\Logging\LogLevel;
use CheckoApi\Logging\StreamLogger;
use CheckoApi\Support\Json;
use CheckoApi\Support\RequestContext;
use CheckoApi\Tests\Support\FrozenClock;
use PHPUnit\Framework\TestCase;

final class StreamLoggerTest extends TestCase
{
    public function testMasksSecretsAndAddsRequestId(): void
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'log');
        $logger = new StreamLogger($file, new RequestContext('abc123'), new FrozenClock(), ['super-secret-key', '']);

        $logger->log(LogLevel::Warning, 'Ошибка', ['url' => 'https://api.checko.ru/v2/company?key=super-secret-key&inn=1']);
        $line = (string) file_get_contents($file);
        unlink($file);

        self::assertStringNotContainsString('super-secret-key', $line);
        self::assertSame('https://api.checko.ru/v2/company?key=***&inn=1', Json::decodeArray($line)['url']);
        self::assertSame('abc123', Json::decodeArray($line)['request_id']);
    }
}
