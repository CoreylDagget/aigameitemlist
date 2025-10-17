<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Auth;

use GameItemsList\Application\Security\JwtTokenService;
use GameItemsList\Domain\Account\Account;
use GameItemsList\Domain\Account\AccountRepositoryInterface;
use RuntimeException;

final class AuthenticateAccountService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accounts,
        private readonly JwtTokenService $jwtTokenService,
        private readonly RefreshTokenService $refreshTokenService
    ) {
    }

    public function authenticate(string $email, string $password): AuthResult
    {
        $account = $this->accounts->findByEmail($email);

        if (!$account instanceof Account) {
            throw new RuntimeException('Invalid credentials');
        }

        if (!password_verify($password, $account->passwordHash())) {
            throw new RuntimeException('Invalid credentials');
        }

        $token = $this->jwtTokenService->issueForAccount($account);
        $refreshToken = $this->refreshTokenService->issueForAccount($account);

        return new AuthResult(
            $account,
            $token->token(),
            $token->expiresIn(),
            $refreshToken->token(),
            $refreshToken->expiresIn()
        );
    }
}
