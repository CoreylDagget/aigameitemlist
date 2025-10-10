<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Infrastructure\Persistence\Lists\PdoItemDefinitionRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoItemDefinitionRepositoryTest extends TestCase
{
    private PDO $pdo;

    private PdoItemDefinitionRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema();
        $this->seedData();

        $this->repository = new PdoItemDefinitionRepository($this->pdo);
    }

    public function testFindByListFiltersByTag(): void
    {
        $items = $this->repository->findByList('list-1', null, 'tag-quest');

        self::assertSame(
            ['item-1', 'item-3'],
            array_map(static fn (ItemDefinition $item): string => $item->id(), $items)
        );
    }

    public function testFindByListFiltersByOwnershipFlag(): void
    {
        $ownedItems = $this->repository->findByList('list-1', 'account-1', null, true);
        self::assertSame(
            ['item-1'],
            array_map(static fn (ItemDefinition $item): string => $item->id(), $ownedItems)
        );

        $unownedItems = $this->repository->findByList('list-1', 'account-1', null, false);
        self::assertSame(
            ['item-2', 'item-3'],
            array_map(static fn (ItemDefinition $item): string => $item->id(), $unownedItems)
        );
    }

    public function testFindByListFiltersBySearchTermCaseInsensitive(): void
    {
        $items = $this->repository->findByList('list-1', null, null, null, 'healing');

        self::assertSame(
            ['item-2'],
            array_map(static fn (ItemDefinition $item): string => $item->id(), $items)
        );
    }

    public function testFindByListCombinesFilters(): void
    {
        $items = $this->repository->findByList('list-1', 'account-1', 'tag-quest', false, null);

        self::assertSame(
            ['item-3'],
            array_map(static fn (ItemDefinition $item): string => $item->id(), $items)
        );
    }

    private function createSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE item_definitions (
                id TEXT PRIMARY KEY,
                list_id TEXT NOT NULL,
                name TEXT NOT NULL,
                description TEXT NULL,
                image_url TEXT NULL,
                storage_type TEXT NOT NULL
            )'
        );

        $this->pdo->exec(
            'CREATE TABLE list_tags (
                id TEXT PRIMARY KEY,
                list_id TEXT NOT NULL,
                name TEXT NOT NULL,
                color TEXT NULL
            )'
        );

        $this->pdo->exec(
            'CREATE TABLE item_definition_tags (
                item_definition_id TEXT NOT NULL,
                tag_id TEXT NOT NULL
            )'
        );

        $this->pdo->exec(
            'CREATE TABLE item_entries (
                id TEXT PRIMARY KEY,
                item_definition_id TEXT NOT NULL,
                account_id TEXT NOT NULL,
                value TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )'
        );
    }

    private function seedData(): void
    {
        $this->pdo->exec(
            "INSERT INTO item_definitions (id, list_id, name, description, image_url, storage_type) VALUES
                ('item-1', 'list-1', 'Lantern', 'Lights the dark caverns', NULL, 'boolean'),
                ('item-2', 'list-1', 'Potion', 'HEALING brew', NULL, 'count'),
                ('item-3', 'list-1', 'Quest Scroll', 'Ancient script', NULL, 'text')"
        );

        $this->pdo->exec(
            "INSERT INTO list_tags (id, list_id, name, color) VALUES
                ('tag-quest', 'list-1', 'Quest', '#FFAA00'),
                ('tag-consumable', 'list-1', 'Consumable', NULL)"
        );

        $this->pdo->exec(
            "INSERT INTO item_definition_tags (item_definition_id, tag_id) VALUES
                ('item-1', 'tag-quest'),
                ('item-2', 'tag-consumable'),
                ('item-3', 'tag-quest')"
        );

        $this->pdo->exec(
            "INSERT INTO item_entries (id, item_definition_id, account_id, value, updated_at) VALUES
                ('entry-1', 'item-1', 'account-1', 'true', '2024-01-01T00:00:00Z'),
                ('entry-2', 'item-2', 'account-2', '5', '2024-01-01T00:00:00Z')"
        );
    }
}
