<?php

declare(strict_types=1);

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Slim\App;

return static function (): App {
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(require __DIR__ . '/dependencies.php');

    $container = $containerBuilder->build();

    return Bridge::create($container);
};
