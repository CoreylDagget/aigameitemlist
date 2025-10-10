<?php

declare(strict_types=1);

use GameItemsList\Application\Action\HealthCheckAction;
use Slim\App;

return static function (App $app): void {
    $app->get('/health', HealthCheckAction::class);
};
