<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Lists;

use GameItemsList\Application\Service\Lists\ItemEntryService;
use GameItemsList\Application\Service\Lists\ListDetailCacheInterface;
use GameItemsList\Application\Service\Lists\ListServiceInterface;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ItemEntry;
use GameItemsList\Domain\Lists\ItemEntryRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ItemEntryServiceTest extends TestCase
{
    public function testSetEntryPersistsBooleanValue(): void
    {
        /** @var MockObject&ListServiceInterface $listService */
        $listService = $this->createMock(ListServiceInterface::class);
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

        /** @var MockObject&ItemDefinitionRepositoryInterface $itemDefinitions */
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

        /** @var MockObject&ItemEntryRepositoryInterface $entries */
        $entries = $this->createMock(ItemEntryRepositoryInterface::class);
        $entries->expects(self::once())
            ->method('upsert')
            ->with('list-1', 'item-1', 'account-1', true, ItemDefinition::STORAGE_BOOLEAN)
            ->willReturn($expectedEntry);

        /** @var MockObject&ListDetailCacheInterface $listCache */
        $listCache = $this->createMock(ListDetailCacheInterface::class);
        $listCache->expects(self::once())
            ->method('invalidateListDetail')
            ->with('account-1', 'list-1');

        $service = new ItemEntryService($listService, $entries, $itemDefinitions, $listCache);

        $result = $service->setEntry('account-1', 'list-1', 'item-1', true);

        self::assertSame($expectedEntry, $result);
    }

    public function testSetEntryThrowsForInvalidCountValue(): void
    {
        /** @var MockObject&ListServiceInterface $listService */
        $listService = $this->createMock(ListServiceInterface::class);
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

        /** @var MockObject&ItemDefinitionRepositoryInterface $itemDefinitions */
        $itemDefinitions = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $itemDefinitions->method('findByIdForList')->willReturn($itemDefinition);

        /** @var MockObject&ItemEntryRepositoryInterface $entries */
        $entries = $this->createMock(ItemEntryRepositoryInterface::class);
        $entries->expects(self::never())->method('upsert');

        /** @var MockObject&ListDetailCacheInterface $listCache */
        $listCache = $this->createMock(ListDetailCacheInterface::class);
        $listCache->expects(self::never())->method('invalidateListDetail');

        $service = new ItemEntryService($listService, $entries, $itemDefinitions, $listCache);

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
