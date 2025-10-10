<?php

declare(strict_types=1);

use GameItemsList\Application\Action\HealthCheckAction;
use GameItemsList\Application\Action\OpenApiAction;
use GameItemsList\Application\Action\SwaggerUiAction;
use Slim\App;

return static function (App $app): void {
    $app->get('/health', HealthCheckAction::class);
    $app->get('/openapi.yaml', OpenApiAction::class);
    $app->get('/docs', SwaggerUiAction::class);
};
