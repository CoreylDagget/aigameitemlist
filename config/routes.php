<?php

declare(strict_types=1);

use GameItemsList\Application\Action\Auth\LoginAction;
use GameItemsList\Application\Action\Auth\RegisterAction;
use GameItemsList\Application\Action\HealthCheckAction;
use GameItemsList\Application\Action\Lists\CreateListAction;
use GameItemsList\Application\Action\Lists\ListIndexAction;
use GameItemsList\Application\Action\Lists\GetListAction;
use GameItemsList\Application\Action\OpenApiAction;
use GameItemsList\Application\Action\SwaggerUiAction;
use GameItemsList\Application\Middleware\AuthenticationMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $app->get('/health', HealthCheckAction::class);
    $app->get('/openapi.yaml', OpenApiAction::class);
    $app->get('/docs', SwaggerUiAction::class);

    $app->post('/v1/auth/register', RegisterAction::class);
    $app->post('/v1/auth/login', LoginAction::class);

    $app->group('/v1/lists', static function (RouteCollectorProxy $group): void {
        $group->get('', ListIndexAction::class);
        $group->post('', CreateListAction::class);
        $group->get('/{listId}', GetListAction::class);
    })->add(AuthenticationMiddleware::class);
};
