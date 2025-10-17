<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

final class ListShareToken
{
    public function __construct(
        private readonly string $id,
        private readonly string $listId,
        private readonly string $token,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?\DateTimeImmutable $revokedAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function listId(): string
    {
        return $this->listId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function revokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null;
    }
}
