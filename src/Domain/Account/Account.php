<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Account;

final class Account
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly bool $isAdmin,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['email'],
            $row['password_hash'],
            (bool) ($row['is_admin'] ?? false),
            new \DateTimeImmutable($row['created_at']),
        );
    }
}
