<?php

declare(strict_types=1);

use App\Application\Pipeline\Stage\CleaningStage;
use App\Application\Pipeline\Stage\SchemaMapStage;
use App\Application\Pipeline\Stage\TokenOptimizationStage;
use App\Application\Pipeline\Stage\ValidationStage;
use App\Application\Pipeline\TransformationPipeline;
use App\Application\UseCase\AnalyzeLegacyDataUseCase;
use App\Application\UseCase\FetchAndTransformUseCase;
use App\Application\UseCase\HealthCheckUseCase;
use App\Domain\Contract\CacheInterface;
use App\Domain\Contract\LegacyDataRepositoryInterface;
use App\Domain\Contract\LlmGatewayInterface;
use App\Infrastructure\Cache\CacheKeyGenerator;
use App\Infrastructure\Cache\FileCacheAdapter;
use App\Infrastructure\Cache\RedisCacheAdapter;
use App\Infrastructure\Config\ConfigLoader;
use App\Infrastructure\ExternalApi\HttpClientFactory;
use App\Infrastructure\ExternalApi\OpenAiGateway;
use App\Infrastructure\Http\Controller\AnalysisController;
use App\Infrastructure\Http\Controller\DataSourceController;
use App\Infrastructure\Http\Controller\HealthController;
use App\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Infrastructure\Http\Middleware\ErrorHandlerMiddleware;
use App\Infrastructure\Http\Middleware\JsonResponseMiddleware;
use App\Infrastructure\Http\Middleware\RateLimitMiddleware;
use App\Infrastructure\Logging\MonologFactory;
use App\Infrastructure\Persistence\ConnectionFactory;
use App\Infrastructure\Persistence\LegacyPdoRepository;
use DI\ContainerBuilder;
use GuzzleHttp\Client;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Build DI Container
$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Config
    ConfigLoader::class => function () {
        return new ConfigLoader(__DIR__ . '/../config');
    },

    // Logger
    LoggerInterface::class => function (ConfigLoader $config) {
        return MonologFactory::create('app', [
            'level' => $config->get('logging.level', 'info'),
            'path' => $config->get('logging.path', __DIR__ . '/../var/log'),
        ]);
    },

    // Database
    ConnectionFactory::class => function (ConfigLoader $config) {
        return new ConnectionFactory($config->get('database', []));
    },

    // Repository
    LegacyDataRepositoryInterface::class => function (ConnectionFactory $factory, ConfigLoader $config) {
        return new LegacyPdoRepository($factory, $config);
    },

    // Cache
    CacheInterface::class => function (ConfigLoader $config, LoggerInterface $logger) {
        $driver = $config->get('cache.driver', 'file');

        if ($driver === 'redis') {
            $redisConfig = $config->get('cache.redis', []);
            $redis = new RedisClient([
                'scheme' => 'tcp',
                'host' => $redisConfig['host'] ?? '127.0.0.1',
                'port' => $redisConfig['port'] ?? 6379,
                'password' => $redisConfig['password'] ?? null,
                'database' => $redisConfig['database'] ?? 0,
            ]);
            return new RedisCacheAdapter($redis, $logger);
        }

        $cachePath = $config->get('cache.file.path', __DIR__ . '/../var/cache');
        return new FileCacheAdapter($cachePath, $logger);
    },

    CacheKeyGenerator::class => fn() => new CacheKeyGenerator(),

    // HTTP Client for OpenAI
    Client::class => function (ConfigLoader $config) {
        return HttpClientFactory::create($config->get('llm', []));
    },

    // LLM Gateway
    LlmGatewayInterface::class => function (Client $client, LoggerInterface $logger, ConfigLoader $config) {
        return new OpenAiGateway(
            $client,
            $logger,
            $config->get('llm.model', 'gpt-4o'),
            $config->get('llm.max_tokens', 4096),
            $config->get('llm.retry_attempts', 3),
            $config->get('llm.retry_delay_ms', 1000),
        );
    },

    // Pipeline (default — can be overridden per source)
    TransformationPipeline::class => function () {
        return new TransformationPipeline(
            new CleaningStage(),
            new ValidationStage(),
            new SchemaMapStage(),
            new TokenOptimizationStage(),
        );
    },

    // Use Cases
    AnalyzeLegacyDataUseCase::class => function (
        LegacyDataRepositoryInterface $repo,
        TransformationPipeline $pipeline,
        LlmGatewayInterface $llm,
        CacheInterface $cache,
        CacheKeyGenerator $keyGen,
        LoggerInterface $logger,
        ConfigLoader $config,
    ) {
        return new AnalyzeLegacyDataUseCase(
            $repo,
            $pipeline,
            $llm,
            $cache,
            $keyGen,
            $logger,
            (int) $config->get('cache.ttl.transform', 3600),
            (int) $config->get('cache.ttl.llm', 86400),
        );
    },

    FetchAndTransformUseCase::class => function (
        LegacyDataRepositoryInterface $repo,
        TransformationPipeline $pipeline,
        CacheInterface $cache,
        CacheKeyGenerator $keyGen,
        LoggerInterface $logger,
        ConfigLoader $config,
    ) {
        return new FetchAndTransformUseCase(
            $repo,
            $pipeline,
            $cache,
            $keyGen,
            $logger,
            (int) $config->get('cache.ttl.transform', 3600),
        );
    },

    HealthCheckUseCase::class => function (
        ConnectionFactory $connFactory,
        CacheInterface $cache,
        Client $httpClient,
    ) {
        return new HealthCheckUseCase($connFactory, $cache, $httpClient);
    },

    // Controllers
    AnalysisController::class => function (AnalyzeLegacyDataUseCase $useCase) {
        return new AnalysisController($useCase);
    },

    DataSourceController::class => function (
        FetchAndTransformUseCase $transformUseCase,
        CacheInterface $cache,
        CacheKeyGenerator $keyGen,
    ) {
        return new DataSourceController(
            $transformUseCase,
            $cache,
            $keyGen,
            __DIR__ . '/../config/schemas',
        );
    },

    HealthController::class => function (HealthCheckUseCase $healthCheck) {
        return new HealthController($healthCheck);
    },
]);

$container = $containerBuilder->build();

// Create Slim App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Parse JSON body
$app->addBodyParsingMiddleware();

// Middleware stack (LIFO order — last added runs first)
$app->add(new ErrorHandlerMiddleware(
    $container->get(LoggerInterface::class),
    (bool) ($container->get(ConfigLoader::class)->get('app.debug', false))
));
$app->add(new RateLimitMiddleware($container->get(CacheInterface::class)));
$app->add(new AuthMiddleware($container->get(ConfigLoader::class)->get('app.api_key', '')));
$app->add(new JsonResponseMiddleware());

// Register routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// Run
$app->run();
