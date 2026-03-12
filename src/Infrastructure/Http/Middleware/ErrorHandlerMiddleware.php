<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\Exception\OrchestratorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;
use Throwable;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (OrchestratorException $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => get_class($e),
                'code' => $e->httpStatusCode(),
                'context' => $e->context(),
                'request_id' => $request->getAttribute('request_id'),
            ]);

            return $this->jsonError(
                $e->httpStatusCode(),
                $this->getErrorCode($e),
                $e->getMessage(),
                $e->context(),
                $request->getAttribute('request_id')
            );
        } catch (Throwable $e) {
            $this->logger->critical('Unhandled exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $this->debug ? $e->getTraceAsString() : null,
                'request_id' => $request->getAttribute('request_id'),
            ]);

            return $this->jsonError(
                500,
                'INTERNAL_ERROR',
                $this->debug ? $e->getMessage() : 'An internal error occurred.',
                [],
                $request->getAttribute('request_id')
            );
        }
    }

    /**
     * @param array<string, mixed> $details
     */
    private function jsonError(
        int $status,
        string $code,
        string $message,
        array $details,
        ?string $requestId
    ): ResponseInterface {
        $response = new Response($status);
        $body = [
            'success' => false,
            'data' => null,
            'meta' => [
                'request_id' => $requestId,
                'timestamp' => date('c'),
            ],
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== []) {
            $body['error']['details'] = $details;
        }

        $response->getBody()->write((string) json_encode($body, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getErrorCode(OrchestratorException $e): string
    {
        $class = (new \ReflectionClass($e))->getShortName();
        return strtoupper((string) preg_replace('/([a-z])([A-Z])/', '$1_$2', str_replace('Exception', '', $class)));
    }
}
