<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Auth;

use GameItemsList\Domain\Account\Account;

final class AuthResult
{
    public function __construct(
        private readonly Account $account,
        private readonly string $accessToken,
        private readonly int $expiresIn,
        private readonly ?string $refreshToken,
        private readonly ?int $refreshTokenExpiresIn
    ) {
    }

    public function account(): Account
    {
        return $this->account;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function expiresIn(): int
    {
        return $this->expiresIn;
    }

    public function refreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function refreshTokenExpiresIn(): ?int
    {
        return $this->refreshTokenExpiresIn;
    }
}
