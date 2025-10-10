<?php
declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Account;

use GameItemsList\Domain\Account\Account;
use GameItemsList\Domain\Account\AccountRepositoryInterface;
use PDO;
use PDOException;
use RuntimeException;

final class PdoAccountRepository implements AccountRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?Account
    {
        $statement = $this->pdo->prepare('SELECT * FROM accounts WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower($email)]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return Account::fromDatabaseRow($row);
    }

    public function findById(string $id): ?Account
    {
        $statement = $this->pdo->prepare('SELECT * FROM accounts WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return Account::fromDatabaseRow($row);
    }

    public function create(string $email, string $passwordHash): Account
    {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO accounts (email, password_hash) VALUES (:email, :password_hash) RETURNING *'
            );
            $statement->execute([
                'email' => strtolower($email),
                'password_hash' => $passwordHash,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to create account', 0, $exception);
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to fetch created account');
        }

        return Account::fromDatabaseRow($row);
    }
}
