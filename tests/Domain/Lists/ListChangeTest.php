<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Domain\Lists;

use DateTimeImmutable;
use GameItemsList\Domain\Lists\ListChange;
use PHPUnit\Framework\TestCase;

final class ListChangeTest extends TestCase
{
    public function testAccessorsExposeConstructorArguments(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $reviewedAt = new DateTimeImmutable('2024-01-02T00:00:00+00:00');
        $payload = ['name' => 'Updated'];
        $change = new ListChange(
            'change-1',
            'list-1',
            'account-1',
            ListChange::TYPE_EDIT_ITEM,
            $payload,
            ListChange::STATUS_PENDING,
            $createdAt,
            'moderator-1',
            $reviewedAt,
        );

        self::assertSame('change-1', $change->id());
        self::assertSame('list-1', $change->listId());
        self::assertSame('account-1', $change->actorAccountId());
        self::assertSame(ListChange::TYPE_EDIT_ITEM, $change->type());
        self::assertSame($payload, $change->payload());
        self::assertSame(ListChange::STATUS_PENDING, $change->status());
        self::assertSame($createdAt, $change->createdAt());
        self::assertSame('moderator-1', $change->reviewedBy());
        self::assertSame($reviewedAt, $change->reviewedAt());
    }

    public function testFromDatabaseRowDecodesJsonPayload(): void
    {
        $change = ListChange::fromDatabaseRow([
            'id' => 'change-2',
            'list_id' => 'list-2',
            'actor_account_id' => 'account-2',
            'type' => ListChange::TYPE_ADD_TAG,
            'payload' => json_encode(['tag' => 'new'], JSON_THROW_ON_ERROR),
            'status' => ListChange::STATUS_APPROVED,
            'created_at' => '2024-03-01T12:00:00+00:00',
            'reviewed_by' => 'moderator-2',
            'reviewed_at' => '2024-03-02T12:00:00+00:00',
        ]);

        self::assertSame(['tag' => 'new'], $change->payload());
        self::assertSame('2024-03-01T12:00:00+00:00', $change->createdAt()->format('c'));
        self::assertSame('2024-03-02T12:00:00+00:00', $change->reviewedAt()?->format('c'));
    }

    public function testFromDatabaseRowHandlesUnexpectedPayload(): void
    {
        $change = ListChange::fromDatabaseRow([
            'id' => 'change-3',
            'list_id' => 'list-3',
            'actor_account_id' => 'account-3',
            'type' => ListChange::TYPE_REMOVE_ITEM,
            'payload' => null,
            'status' => ListChange::STATUS_REJECTED,
            'created_at' => '2024-04-01T00:00:00+00:00',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        self::assertSame([], $change->payload());
        self::assertNull($change->reviewedBy());
        self::assertNull($change->reviewedAt());
    }
}
