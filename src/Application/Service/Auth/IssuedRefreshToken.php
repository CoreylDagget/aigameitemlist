<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Auth;

final class IssuedRefreshToken
{
    public function __construct(
        private readonly string $token,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly int $expiresIn
    ) {
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function expiresIn(): int
    {
        return $this->expiresIn;
    }
}
