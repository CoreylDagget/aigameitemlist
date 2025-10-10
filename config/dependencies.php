<?php

declare(strict_types=1);

use GameItemsList\Application\Action\HealthCheckAction;
use GameItemsList\Application\Action\OpenApiAction;
use GameItemsList\Application\Action\SwaggerUiAction;

return [
    HealthCheckAction::class => static fn(): HealthCheckAction => new HealthCheckAction(),
    OpenApiAction::class => static fn(): OpenApiAction => new OpenApiAction(dirname(__DIR__) . '/openapi.yaml'),
    SwaggerUiAction::class => static fn(): SwaggerUiAction => new SwaggerUiAction('/openapi.yaml'),
];
