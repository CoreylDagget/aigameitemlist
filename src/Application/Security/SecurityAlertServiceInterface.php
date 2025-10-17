<?php

declare(strict_types=1);

namespace GameItemsList\Application\Security;

interface SecurityAlertServiceInterface
{
    public function notifyRefreshTokenReuse(string $accountId): void;
}
