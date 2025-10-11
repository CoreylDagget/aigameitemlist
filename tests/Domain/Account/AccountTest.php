<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Domain\Account;

use DateTimeImmutable;
use GameItemsList\Domain\Account\Account;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function testAccessorsExposeConstructorValues(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00Z');
        $account = new Account('acc-1', 'user@example.com', 'hash', true, $createdAt);

        self::assertSame('acc-1', $account->id());
        self::assertSame('user@example.com', $account->email());
        self::assertSame('hash', $account->passwordHash());
        self::assertTrue($account->isAdmin());
        self::assertSame($createdAt, $account->createdAt());
    }

    public function testFromDatabaseRowCreatesDomainObject(): void
    {
        $account = Account::fromDatabaseRow([
            'id' => 'acc-2',
            'email' => 'second@example.com',
            'password_hash' => 'hashed',
            'is_admin' => 0,
            'created_at' => '2024-02-02T12:34:56+00:00',
        ]);

        self::assertSame('acc-2', $account->id());
        self::assertSame('second@example.com', $account->email());
        self::assertSame('hashed', $account->passwordHash());
        self::assertFalse($account->isAdmin());
        self::assertSame('2024-02-02T12:34:56+00:00', $account->createdAt()->format('c'));
    }
}
