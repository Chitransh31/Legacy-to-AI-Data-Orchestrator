<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\DTO\AnalysisRequest;
use App\Application\Pipeline\Stage\CleaningStage;
use App\Application\Pipeline\TransformationPipeline;
use App\Application\UseCase\AnalyzeLegacyDataUseCase;
use App\Domain\Contract\CacheInterface;
use App\Domain\Contract\LegacyDataRepositoryInterface;
use App\Domain\Contract\LlmGatewayInterface;
use App\Domain\Entity\AnalysisResult;
use App\Domain\Entity\LegacyRecord;
use App\Domain\Entity\TransformedPayload;
use App\Domain\ValueObject\CacheKey;
use App\Domain\ValueObject\DataSourceId;
use App\Infrastructure\Cache\CacheKeyGenerator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnalyzeLegacyDataUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecutesFetchTransformAndAnalyze(): void
    {
        $source = new DataSourceId('erp_orders');
        $query = ['ORD_STATUS' => 'SHIPPED'];

        // Mock repository
        $repository = Mockery::mock(LegacyDataRepositoryInterface::class);
        $repository->shouldReceive('fetchBatch')
            ->once()
            ->with(Mockery::on(fn($s) => $s->value() === 'erp_orders'), $query)
            ->andReturn([
                new LegacyRecord($source, ['ORD_ID' => 'ORD-001', 'CUST_NM' => 'Acme']),
            ]);

        // Mock cache (all misses)
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->andReturn(null);
        $cache->shouldReceive('set')->twice(); // transform cache + llm cache

        // Mock LLM gateway
        $llmGateway = Mockery::mock(LlmGatewayInterface::class);
        $llmGateway->shouldReceive('analyze')
            ->once()
            ->andReturn(new AnalysisResult('Analysis result content', 'gpt-4o', 500, 1200.5));

        $pipeline = new TransformationPipeline(new CleaningStage());
        $keyGen = new CacheKeyGenerator();

        $useCase = new AnalyzeLegacyDataUseCase(
            $repository,
            $pipeline,
            $llmGateway,
            $cache,
            $keyGen,
            new NullLogger(),
        );

        $request = new AnalysisRequest($source, $query, 'Summarize trends');
        $response = $useCase->execute($request);

        $this->assertSame('Analysis result content', $response->content);
        $this->assertSame('gpt-4o', $response->model);
        $this->assertSame(500, $response->tokensUsed);
        $this->assertFalse($response->cached);
        $this->assertSame(1, $response->recordCount);
    }

    public function testReturnsCachedLlmResponse(): void
    {
        $source = new DataSourceId('erp_orders');

        // Mock repository (should NOT be called for full LLM cache hit)
        $repository = Mockery::mock(LegacyDataRepositoryInterface::class);
        $repository->shouldReceive('fetchBatch')
            ->once()
            ->andReturn([new LegacyRecord($source, ['ORD_ID' => 'ORD-001'])]);

        // Mock cache — transform miss, but LLM hit
        $cache = Mockery::mock(CacheInterface::class);
        $transformCacheHit = false;
        $cache->shouldReceive('get')->andReturnUsing(function (CacheKey $key) use (&$transformCacheHit) {
            $keyStr = $key->value();
            if (str_starts_with($keyStr, 'llm:')) {
                return json_encode([
                    'content' => 'Cached analysis',
                    'model' => 'gpt-4o',
                    'tokens_used' => 300,
                    'latency_ms' => 0,
                    'cached' => true,
                ]);
            }
            return null;
        });
        $cache->shouldReceive('set');

        // Mock LLM (should NOT be called)
        $llmGateway = Mockery::mock(LlmGatewayInterface::class);
        $llmGateway->shouldNotReceive('analyze');

        $pipeline = new TransformationPipeline(new CleaningStage());
        $keyGen = new CacheKeyGenerator();

        $useCase = new AnalyzeLegacyDataUseCase(
            $repository,
            $pipeline,
            $llmGateway,
            $cache,
            $keyGen,
            new NullLogger(),
        );

        $request = new AnalysisRequest($source, [], 'Analyze data');
        $response = $useCase->execute($request);

        $this->assertSame('Cached analysis', $response->content);
        $this->assertTrue($response->cached);
    }
}
