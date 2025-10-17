<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Fixtures;

use GameItemsList\Application\Security\SecurityAlertServiceInterface;

final class SpySecurityAlertService implements SecurityAlertServiceInterface
{
    /**
     * @var array<int, string>
     */
    private array $alerts = [];

    public function notifyRefreshTokenReuse(string $accountId): void
    {
        $this->alerts[] = $accountId;
    }

    /**
     * @return array<int, string>
     */
    public function alerts(): array
    {
        return $this->alerts;
    }
}
