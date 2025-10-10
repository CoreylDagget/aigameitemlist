<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Domain\Lists;

use DateTimeImmutable;
use GameItemsList\Domain\Lists\ItemEntry;
use PHPUnit\Framework\TestCase;

final class ItemEntryTest extends TestCase
{
    public function testAccessors(): void
    {
        $updatedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $entry = new ItemEntry('entry-1', 'list-1', 'item-1', 'account-1', 5, $updatedAt);

        self::assertSame('entry-1', $entry->id());
        self::assertSame('list-1', $entry->listId());
        self::assertSame('item-1', $entry->itemDefinitionId());
        self::assertSame('account-1', $entry->accountId());
        self::assertSame(5, $entry->value());
        self::assertSame($updatedAt, $entry->updatedAt());
    }

    /**
     * @dataProvider valueProvider
     *
     * @param array<string, mixed> $row
     */
    public function testFromDatabaseRowCastsStoredValues(array $row, bool|int|string $expected): void
    {
        $row = array_merge([
            'id' => 'entry-2',
            'list_id' => 'list-1',
            'item_definition_id' => 'item-1',
            'account_id' => 'account-1',
            'updated_at' => '2024-02-02T03:04:05+00:00',
        ], $row);

        $entry = ItemEntry::fromDatabaseRow($row);

        self::assertSame($expected, $entry->value());
        self::assertSame('2024-02-02T03:04:05+00:00', $entry->updatedAt()->format('c'));
    }

    /**
     * @return iterable<string, array{row: array<string, mixed>, expected: bool|int|string}>
     */
    public static function valueProvider(): iterable
    {
        yield 'boolean value' => [
            'row' => ['value_boolean' => 1, 'value_integer' => null, 'value_text' => null],
            'expected' => true,
        ];

        yield 'integer value' => [
            'row' => ['value_boolean' => null, 'value_integer' => 7, 'value_text' => null],
            'expected' => 7,
        ];

        yield 'text value' => [
            'row' => ['value_boolean' => null, 'value_integer' => null, 'value_text' => 'owned'],
            'expected' => 'owned',
        ];
    }
}
