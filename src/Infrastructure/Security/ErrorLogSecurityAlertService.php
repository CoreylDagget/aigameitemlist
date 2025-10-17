<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Security;

use GameItemsList\Application\Security\SecurityAlertServiceInterface;

final class ErrorLogSecurityAlertService implements SecurityAlertServiceInterface
{
    public function notifyRefreshTokenReuse(string $accountId): void
    {
        error_log(sprintf('Refresh token reuse detected for account %s', $accountId));
    }
}
