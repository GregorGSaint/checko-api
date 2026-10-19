<?php

declare(strict_types=1);

namespace CheckoApi\Bootstrap;

use CheckoApi\Auth\TokenService;
use CheckoApi\Checko\CachingCheckoClient;
use CheckoApi\Checko\CheckoApiClient;
use CheckoApi\Checko\CheckoClientInterface;
use CheckoApi\Checko\Demo\FixtureHttpClient;
use CheckoApi\Checko\Mapping\CompanyMapper;
use CheckoApi\Checko\Mapping\EntrepreneurMapper;
use CheckoApi\Checko\Mapping\FinancesMapper;
use CheckoApi\Checko\Mapping\SearchMapper;
use CheckoApi\Checko\Quota\QuotaGuard;
use CheckoApi\Checko\Quota\RecordingHttpClient;
use CheckoApi\Console\Console;
use CheckoApi\Console\CreateTokenCommand;
use CheckoApi\Console\MigrateCommand;
use CheckoApi\Console\Output;
use CheckoApi\Console\PruneCacheCommand;
use CheckoApi\Console\SeedDemoTokenCommand;
use CheckoApi\Http\Api;
use CheckoApi\Http\Controller\CompanyController;
use CheckoApi\Http\Controller\EntrepreneurController;
use CheckoApi\Http\Controller\HealthController;
use CheckoApi\Http\Controller\SearchController;
use CheckoApi\Http\ErrorHandler;
use CheckoApi\Http\Middleware\AuthenticationMiddleware;
use CheckoApi\Http\Middleware\ErrorMiddleware;
use CheckoApi\Http\Middleware\RateLimitMiddleware;
use CheckoApi\Http\Middleware\RequestLogMiddleware;
use CheckoApi\Http\Middleware\ServiceHeadersMiddleware;
use CheckoApi\Http\Responder;
use CheckoApi\Logging\LoggerInterface;
use CheckoApi\Logging\StreamLogger;
use CheckoApi\Persistence\Database;
use CheckoApi\Persistence\Migrator;
use CheckoApi\Persistence\SqliteCache;
use CheckoApi\Persistence\SqliteRequestLog;
use CheckoApi\Persistence\SqliteTokenStore;
use CheckoApi\Persistence\SqliteUpstreamCallLog;
use CheckoApi\Support\ClockInterface;
use CheckoApi\Support\DailyWindow;
use CheckoApi\Support\RequestContext;
use CheckoApi\Support\SystemClock;
use CheckoApi\Transport\CurlHttpClient;
use CheckoApi\Transport\HttpClientInterface;
use CheckoApi\Transport\RetryingHttpClient;
use CheckoApi\Transport\RetryPolicy;
use CheckoApi\Transport\SleeperInterface;
use CheckoApi\Transport\UsleepSleeper;
use PDO;

final class Services
{
    private const PROTECTED_PREFIX = '/v1';

    private RequestContext $context;
    private DailyWindow $window;
    private ?PDO $pdo;
    private ?LoggerInterface $logger;

    public function __construct(
        private readonly Config $config,
        private readonly ClockInterface $clock = new SystemClock(),
        private readonly ?HttpClientInterface $transport = null,
        private readonly SleeperInterface $sleeper = new UsleepSleeper(),
        ?LoggerInterface $logger = null,
        ?PDO $pdo = null,
    ) {
        $this->context = RequestContext::generate();
        $this->window = DailyWindow::moscow();
        $this->logger = $logger;
        $this->pdo = $pdo;
    }

    public static function fromProjectDir(string $projectDir): self
    {
        return new self(Config::load($projectDir));
    }

    public function api(): Api
    {
        $checko = $this->checko();
        $responder = new Responder();
        $requestLog = new SqliteRequestLog($this->pdo());

        return new Api(
            new CompanyController($checko, new CompanyMapper(), new FinancesMapper(), $responder),
            new EntrepreneurController($checko, new EntrepreneurMapper(), $responder),
            new SearchController($checko, new SearchMapper(), $responder),
            new HealthController(
                $this->pdo(),
                $this->quotaGuard(),
                new SqliteUpstreamCallLog($this->pdo()),
                $this->clock,
                $this->window,
                $this->config->isDemoMode(),
            ),
            [
                new ServiceHeadersMiddleware($this->context, $this->config->isDemoMode()),
                new RequestLogMiddleware($requestLog, $this->context, $this->clock, $this->logger()),
                new ErrorMiddleware(new ErrorHandler($this->logger()), $this->context),
                new AuthenticationMiddleware($this->tokens(), $this->context, self::PROTECTED_PREFIX),
                new RateLimitMiddleware($requestLog, $this->clock, $this->window),
            ],
        );
    }

    public function console(): Console
    {
        return new Console([
            new MigrateCommand(new Migrator($this->pdo(), $this->config->projectDir . '/migrations', $this->clock)),
            new CreateTokenCommand($this->tokens(), $this->config->tokenDailyLimit),
            new SeedDemoTokenCommand($this->tokens(), $this->config->demoToken, $this->config->tokenDailyLimit),
            new PruneCacheCommand(new SqliteCache($this->pdo()), $this->clock),
        ], new Output());
    }

    public function tokens(): TokenService
    {
        return new TokenService(new SqliteTokenStore($this->pdo()), $this->clock);
    }

    private function checko(): CheckoClientInterface
    {
        $client = new CheckoApiClient(
            $this->checkoTransport(),
            $this->quotaGuard(),
            $this->clock,
            $this->config->checkoBaseUrl,
            $this->config->checkoApiKey,
        );

        return new CachingCheckoClient(
            $client,
            new SqliteCache($this->pdo()),
            $this->clock,
            $this->logger(),
            $this->config->isDemoMode() ? 'fixtures' : 'live',
        );
    }

    private function checkoTransport(): HttpClientInterface
    {
        if ($this->config->isDemoMode()) {
            return new FixtureHttpClient($this->config->projectDir . '/resources/fixtures/checko');
        }

        $recording = new RecordingHttpClient(
            $this->transport ?? new CurlHttpClient($this->config->checkoTimeout),
            new SqliteUpstreamCallLog($this->pdo()),
            $this->context,
            $this->clock,
            $this->logger(),
        );

        return new RetryingHttpClient($recording, new RetryPolicy(), $this->sleeper);
    }

    private function quotaGuard(): QuotaGuard
    {
        return new QuotaGuard(new SqliteUpstreamCallLog($this->pdo()), $this->clock, $this->window, $this->config->checkoDailyLimit);
    }

    private function pdo(): PDO
    {
        return $this->pdo ??= Database::connect($this->config->databasePath);
    }

    private function logger(): LoggerInterface
    {
        return $this->logger ??= new StreamLogger(
            $this->config->logStream,
            $this->context,
            $this->clock,
            [$this->config->checkoApiKey],
        );
    }
}
