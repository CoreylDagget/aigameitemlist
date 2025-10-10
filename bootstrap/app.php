<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\App;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');

$appFactory = require __DIR__ . '/../config/container.php';
/** @var App $app */
$app = $appFactory();

$settings = require __DIR__ . '/../config/settings.php';
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$app->addErrorMiddleware(
    (bool) ($settings['displayErrorDetails'] ?? false),
    (bool) ($settings['logErrors'] ?? true),
    (bool) ($settings['logErrorDetails'] ?? true)
);

$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

return $app;
