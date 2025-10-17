<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Auth;

use GameItemsList\Domain\Account\Account;

final class RefreshTokenRotationResult
{
    public function __construct(
        private readonly Account $account,
        private readonly IssuedRefreshToken $refreshToken
    ) {
    }

    public function account(): Account
    {
        return $this->account;
    }

    public function refreshToken(): IssuedRefreshToken
    {
        return $this->refreshToken;
    }
}
