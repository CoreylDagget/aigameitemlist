<?php
declare(strict_types=1);

namespace GameItemsList\Domain\Account;

interface AccountRepositoryInterface
{
    public function findByEmail(string $email): ?Account;

    public function findById(string $id): ?Account;

    public function create(string $email, string $passwordHash): Account;
}
