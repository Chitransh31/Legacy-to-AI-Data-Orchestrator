<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

use App\Domain\Contract\LlmGatewayInterface;
use App\Domain\Entity\AnalysisResult;
use App\Domain\Entity\TransformedPayload;
use App\Domain\Exception\LlmApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

final class OpenAiGateway implements LlmGatewayInterface
{
    public function __construct(
        private Client $httpClient,
        private LoggerInterface $logger,
        private string $model = 'gpt-4o',
        private int $maxTokens = 4096,
        private int $retryAttempts = 3,
        private int $retryDelayMs = 1000,
    ) {
    }

    public function analyze(TransformedPayload $payload, string $prompt): AnalysisResult
    {
        $startTime = microtime(true);

        $body = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a data analyst. Analyze the provided data and give insights.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt . "\n\nData:\n" . $payload->toJson(),
                ],
            ],
        ];

        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                $response = $this->httpClient->post('/chat/completions', [
                    'json' => $body,
                ]);

                $responseBody = json_decode(
                    $response->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $content = $responseBody['choices'][0]['message']['content'] ?? '';
                $tokensUsed = $responseBody['usage']['total_tokens'] ?? 0;
                $latencyMs = (microtime(true) - $startTime) * 1000;

                $this->logger->info('LLM analysis completed', [
                    'model' => $this->model,
                    'tokens' => $tokensUsed,
                    'latency_ms' => round($latencyMs, 2),
                    'attempt' => $attempt,
                ]);

                return new AnalysisResult($content, $this->model, $tokensUsed, $latencyMs);
            } catch (ConnectException $e) {
                $lastException = $e;
                $this->logger->warning('LLM connection failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            } catch (RequestException $e) {
                $lastException = $e;
                $statusCode = $e->getResponse()?->getStatusCode();

                if ($statusCode === 429) {
                    $retryAfter = (int) ($e->getResponse()?->getHeaderLine('Retry-After') ?: 60);
                    $this->logger->warning('LLM rate limited', ['retry_after' => $retryAfter]);
                    throw LlmApiException::rateLimited($retryAfter);
                }

                $this->logger->warning('LLM request failed', [
                    'attempt' => $attempt,
                    'status' => $statusCode,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($attempt < $this->retryAttempts) {
                $delay = $this->retryDelayMs * (2 ** ($attempt - 1));
                usleep($delay * 1000);
            }
        }

        $this->logger->error('LLM analysis failed after all retries', [
            'attempts' => $this->retryAttempts,
        ]);

        throw LlmApiException::requestFailed(
            $lastException?->getMessage() ?? 'Unknown error after retries'
        );
    }
}
