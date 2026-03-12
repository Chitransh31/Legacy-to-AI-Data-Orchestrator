<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controller\AnalysisController;
use App\Infrastructure\Http\Controller\DataSourceController;
use App\Infrastructure\Http\Controller\HealthController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/api/v1', function (RouteCollectorProxy $group) {
        // Analysis
        $group->post('/analyze', [AnalysisController::class, 'analyze']);

        // Transform (preview)
        $group->post('/transform', [DataSourceController::class, 'transform']);

        // Data sources
        $group->get('/sources', [DataSourceController::class, 'listSources']);
        $group->get('/sources/{id}/schema', [DataSourceController::class, 'getSchema']);

        // Cache management
        $group->delete('/cache/{sourceId}', [DataSourceController::class, 'invalidateCache']);

        // Health
        $group->get('/health', HealthController::class);
    });
};
