<?php

declare(strict_types=1);

use Slim\Middleware\ErrorMiddleware;

return [
    'displayErrorDetails' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    'logErrors' => true,
    'logErrorDetails' => true,
];
