<?php
declare(strict_types=1);

namespace GameItemsList\Application\Security;

final class IssuedToken
{
    public function __construct(
        private readonly string $token,
        private readonly int $expiresIn
    ) {
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expiresIn(): int
    {
        return $this->expiresIn;
    }
}
