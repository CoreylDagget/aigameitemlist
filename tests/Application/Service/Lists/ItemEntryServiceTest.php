<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Lists;

use GameItemsList\Application\Service\Lists\ItemEntryService;
use GameItemsList\Application\Service\Lists\ListService;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ItemEntry;
use GameItemsList\Domain\Lists\ItemEntryRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ItemEntryServiceTest extends TestCase
{
    public function testSetEntryPersistsBooleanValue(): void
    {
        $listService = $this->createMock(ListService::class);
        $listService->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-1', 'list-1', self::isType('string'))
            ->willReturn($this->createList());

        $itemDefinition = new ItemDefinition(
            'item-1',
            'list-1',
            'Lantern',
            null,
            null,
            ItemDefinition::STORAGE_BOOLEAN,
            []
        );

        $itemDefinitions = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $itemDefinitions->expects(self::once())
            ->method('findByIdForList')
            ->with('item-1', 'list-1')
            ->willReturn($itemDefinition);

        $expectedEntry = new ItemEntry(
            'entry-1',
            'list-1',
            'item-1',
            'account-1',
            true,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $entries = $this->createMock(ItemEntryRepositoryInterface::class);
        $entries->expects(self::once())
            ->method('upsert')
            ->with('list-1', 'item-1', 'account-1', true, ItemDefinition::STORAGE_BOOLEAN)
            ->willReturn($expectedEntry);

        $service = new ItemEntryService($listService, $entries, $itemDefinitions);

        $result = $service->setEntry('account-1', 'list-1', 'item-1', true);

        self::assertSame($expectedEntry, $result);
    }

    public function testSetEntryThrowsForInvalidCountValue(): void
    {
        $listService = $this->createMock(ListService::class);
        $listService->method('requireListOwnedByAccount')->willReturn($this->createList());

        $itemDefinition = new ItemDefinition(
            'item-2',
            'list-1',
            'Potion',
            null,
            null,
            ItemDefinition::STORAGE_COUNT,
            []
        );

        $itemDefinitions = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $itemDefinitions->method('findByIdForList')->willReturn($itemDefinition);

        $entries = $this->createMock(ItemEntryRepositoryInterface::class);
        $entries->expects(self::never())->method('upsert');

        $service = new ItemEntryService($listService, $entries, $itemDefinitions);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be zero or greater for count storage.');

        $service->setEntry('account-1', 'list-1', 'item-2', -5);
    }

    private function createList(): GameList
    {
        return new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Elden Ring'),
            'My List',
            null,
            false,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );
    }
}

