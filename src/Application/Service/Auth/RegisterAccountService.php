<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Auth;

use GameItemsList\Application\Security\JwtTokenService;
use GameItemsList\Domain\Account\AccountRepositoryInterface;
use InvalidArgumentException;

final class RegisterAccountService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accounts,
        private readonly JwtTokenService $jwtTokenService
    ) {
    }

    public function register(string $email, string $password): AuthResult
    {
        if ($this->accounts->findByEmail($email) !== null) {
            throw new InvalidArgumentException('Account already exists');
        }

        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);

        if ($passwordHash === false) {
            throw new InvalidArgumentException('Unable to hash password');
        }

        $account = $this->accounts->create($email, $passwordHash);
        $token = $this->jwtTokenService->issueForAccount($account);

        return new AuthResult($account, $token->token(), $token->expiresIn(), null);
    }
}
