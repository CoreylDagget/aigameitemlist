<?php

declare(strict_types=1);

use GameItemsList\Application\Action\Admin\ApproveChangeAction;
use GameItemsList\Application\Action\Admin\ListChangesAction;
use GameItemsList\Application\Action\Admin\RejectChangeAction;
use GameItemsList\Application\Action\Auth\LoginAction;
use GameItemsList\Application\Action\Auth\RegisterAction;
use GameItemsList\Application\Action\HealthCheckAction;
use GameItemsList\Application\Action\Entries\ListEntriesAction;
use GameItemsList\Application\Action\Entries\SetEntryAction;
use GameItemsList\Application\Action\Items\CreateItemAction;
use GameItemsList\Application\Action\Items\ListItemsAction;
use GameItemsList\Application\Action\Items\UpdateItemAction;
use GameItemsList\Application\Action\Lists\CreateListAction;
use GameItemsList\Application\Action\Lists\ListIndexAction;
use GameItemsList\Application\Action\Lists\GetListAction;
use GameItemsList\Application\Action\Lists\PublishListAction;
use GameItemsList\Application\Action\Lists\UpdateListAction;
use GameItemsList\Application\Action\OpenApiAction;
use GameItemsList\Application\Action\SwaggerUiAction;
use GameItemsList\Application\Action\Tags\CreateTagAction;
use GameItemsList\Application\Action\Tags\ListTagsAction;
use GameItemsList\Application\Middleware\AdminAuthorizationMiddleware;
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
        $group->patch('/{listId}', UpdateListAction::class);
        $group->post('/{listId}/publish', PublishListAction::class);
        $group->get('/{listId}/tags', ListTagsAction::class);
        $group->post('/{listId}/tags', CreateTagAction::class);
        $group->get('/{listId}/items', ListItemsAction::class);
        $group->post('/{listId}/items', CreateItemAction::class);
        $group->patch('/{listId}/items/{itemId}', UpdateItemAction::class);
        $group->get('/{listId}/entries', ListEntriesAction::class);
        $group->post('/{listId}/entries/{itemId}', SetEntryAction::class);
    })->add(AuthenticationMiddleware::class);

    $app->group('/v1/admin', static function (RouteCollectorProxy $group): void {
        $group->get('/changes', ListChangesAction::class);
        $group->post('/changes/{changeId}/approve', ApproveChangeAction::class);
        $group->post('/changes/{changeId}/reject', RejectChangeAction::class);
    })->add(AdminAuthorizationMiddleware::class)
        ->add(AuthenticationMiddleware::class);
};
