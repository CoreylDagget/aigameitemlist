<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Lists;

use DateTimeImmutable;
use GameItemsList\Application\Service\Lists\ItemDefinitionService;
use GameItemsList\Application\Service\Lists\ListServiceInterface;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use GameItemsList\Domain\Lists\Tag;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ItemDefinitionServiceTest extends TestCase
{
    /** @var ListServiceInterface&MockObject */
    private ListServiceInterface $listService;

    /** @var ItemDefinitionRepositoryInterface&MockObject */
    private ItemDefinitionRepositoryInterface $itemDefinitions;

    /** @var TagRepositoryInterface&MockObject */
    private TagRepositoryInterface $tags;

    /** @var ListChangeRepositoryInterface&MockObject */
    private ListChangeRepositoryInterface $listChanges;

    private ItemDefinitionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listService = $this->createMock(ListServiceInterface::class);
        $this->itemDefinitions = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $this->tags = $this->createMock(TagRepositoryInterface::class);
        $this->listChanges = $this->createMock(ListChangeRepositoryInterface::class);

        $this->service = new ItemDefinitionService(
            $this->listService,
            $this->itemDefinitions,
            $this->tags,
            $this->listChanges,
        );
    }

    public function testListItemsWithOwnedFilterUsesAccountId(): void
    {
        $this->listService
            ->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-1', 'list-1', 'You are not allowed to view this list.')
            ->willReturn($this->createStub(GameList::class));

        $expected = ['item'];

        $this->itemDefinitions
            ->expects(self::once())
            ->method('findByList')
            ->with('list-1', 'account-1', 'tag-9', true, 'potion')
            ->willReturn($expected);

        $items = $this->service->listItems('account-1', 'list-1', 'tag-9', true, 'potion');

        self::assertSame($expected, $items);
    }

    public function testListItemsWithoutOwnedFilterPassesNullAccount(): void
    {
        $this->listService
            ->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-2', 'list-5', 'You are not allowed to view this list.')
            ->willReturn($this->createStub(GameList::class));

        $expected = [];

        $this->itemDefinitions
            ->expects(self::once())
            ->method('findByList')
            ->with('list-5', null, null, null, null)
            ->willReturn($expected);

        $items = $this->service->listItems('account-2', 'list-5');

        self::assertSame($expected, $items);
    }

    public function testProposeCreateItemNormalizesInputAndCreatesListChange(): void
    {
        $this->listService
            ->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-1', 'list-1', 'You are not allowed to modify this list.')
            ->willReturn($this->createStub(GameList::class));

        $this->tags
            ->expects(self::once())
            ->method('findByIds')
            ->with('list-1', ['tag-1', 'tag-2'])
            ->willReturn([
                new Tag('tag-1', 'list-1', 'Tag 1', null),
                new Tag('tag-2', 'list-1', 'Tag 2', null),
            ]);

        $expectedChange = $this->createListChange(ListChange::TYPE_ADD_ITEM, [
            'name' => 'Potion',
        ]);

        $this->listChanges
            ->expects(self::once())
            ->method('create')
            ->with(
                'list-1',
                'account-1',
                ListChange::TYPE_ADD_ITEM,
                self::callback(function (array $payload): bool {
                    self::assertSame('Potion', $payload['name']);
                    self::assertSame(ItemDefinition::STORAGE_COUNT, $payload['storageType']);
                    self::assertSame('Strong potion', $payload['description']);
                    self::assertSame('https://example.com/potion.png', $payload['imageUrl']);
                    self::assertSame(['tag-1', 'tag-2'], $payload['tagIds']);

                    return true;
                })
            )
            ->willReturn($expectedChange);

        $change = $this->service->proposeCreateItem(
            'account-1',
            'list-1',
            '  Potion  ',
            'COUNT',
            '  Strong potion  ',
            ' https://example.com/potion.png ',
            ['tag-1', 'tag-2', 'tag-1'],
        );

        self::assertSame($expectedChange, $change);
    }

    public function testProposeCreateItemRejectsUnknownTags(): void
    {
        $this->listService
            ->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-1', 'list-1', 'You are not allowed to modify this list.')
            ->willReturn($this->createStub(GameList::class));

        $this->tags
            ->expects(self::once())
            ->method('findByIds')
            ->with('list-1', ['tag-missing'])
            ->willReturn([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('One or more tagIds are invalid for this list.');

        $this->service->proposeCreateItem(
            'account-1',
            'list-1',
            'Sword',
            ItemDefinition::STORAGE_TEXT,
            null,
            null,
            ['tag-missing'],
        );
    }

    public function testProposeUpdateItemCollectsChangedFields(): void
    {
        $this->listService
            ->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-3', 'list-9', 'You are not allowed to modify this list.')
            ->willReturn($this->createStub(GameList::class));

        $existingTags = [new Tag('tag-1', 'list-9', 'Existing', null)];
        $existingItem = new ItemDefinition(
            'item-1',
            'list-9',
            'Potion',
            'Restores health',
            'https://example.com/old.png',
            ItemDefinition::STORAGE_COUNT,
            $existingTags,
        );

        $this->itemDefinitions
            ->expects(self::once())
            ->method('findByIdForList')
            ->with('item-1', 'list-9')
            ->willReturn($existingItem);

        $this->tags
            ->expects(self::once())
            ->method('findByIds')
            ->with('list-9', ['tag-2', 'tag-1'])
            ->willReturn([
                new Tag('tag-2', 'list-9', 'New', null),
                ...$existingTags,
            ]);

        $expectedChange = $this->createListChange(ListChange::TYPE_EDIT_ITEM, [
            'itemId' => 'item-1',
        ]);

        $this->listChanges
            ->expects(self::once())
            ->method('create')
            ->with(
                'list-9',
                'account-3',
                ListChange::TYPE_EDIT_ITEM,
                self::callback(function (array $payload): bool {
                    self::assertSame('item-1', $payload['itemId']);
                    self::assertSame('Mega Potion', $payload['name']);
                    self::assertSame('Even stronger', $payload['description']);
                    self::assertSame('https://example.com/new.png', $payload['imageUrl']);
                    self::assertSame(ItemDefinition::STORAGE_TEXT, $payload['storageType']);
                    self::assertSame(['tag-1', 'tag-2'], $payload['tagIds']);

                    return true;
                })
            )
            ->willReturn($expectedChange);

        $change = $this->service->proposeUpdateItem(
            'account-3',
            'list-9',
            'item-1',
            [
                'name' => '  Mega Potion ',
                'description' => ' Even stronger ',
                'imageUrl' => ' https://example.com/new.png ',
                'storageType' => 'TEXT',
                'tagIds' => ['tag-2', 'tag-1', 'tag-2'],
            ],
        );

        self::assertSame($expectedChange, $change);
    }

    public function testProposeUpdateItemWithoutRealChangesThrowsException(): void
    {
        $this->listService
            ->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-4', 'list-3', 'You are not allowed to modify this list.')
            ->willReturn($this->createStub(GameList::class));

        $tag = new Tag('tag-1', 'list-3', 'Existing', null);
        $existingItem = new ItemDefinition(
            'item-77',
            'list-3',
            'Potion',
            'Restores health',
            'https://example.com/potion.png',
            ItemDefinition::STORAGE_COUNT,
            [$tag],
        );

        $this->itemDefinitions
            ->expects(self::once())
            ->method('findByIdForList')
            ->with('item-77', 'list-3')
            ->willReturn($existingItem);

        $this->tags
            ->expects(self::once())
            ->method('findByIds')
            ->with('list-3', ['tag-1'])
            ->willReturn([$tag]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No valid changes provided.');

        $this->service->proposeUpdateItem(
            'account-4',
            'list-3',
            'item-77',
            [
                'name' => 'Potion',
                'description' => 'Restores health',
                'imageUrl' => 'https://example.com/potion.png',
                'storageType' => 'count',
                'tagIds' => ['tag-1'],
            ],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createListChange(string $type, array $payload): ListChange
    {
        return new ListChange(
            'change-1',
            'list-any',
            'account-any',
            $type,
            $payload,
            ListChange::STATUS_PENDING,
            new DateTimeImmutable(),
        );
    }
}
