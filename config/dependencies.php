<?php

declare(strict_types=1);

use GameItemsList\Application\Action\HealthCheckAction;

return [
    HealthCheckAction::class => static fn(): HealthCheckAction => new HealthCheckAction(),
];
