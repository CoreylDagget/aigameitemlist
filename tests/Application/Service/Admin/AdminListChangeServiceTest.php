<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Admin;

use DateTimeImmutable;
use DomainException;
use GameItemsList\Application\Service\Admin\AdminListChangeService;
use GameItemsList\Application\Service\Lists\ListDetailCacheInterface;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AdminListChangeServiceTest extends TestCase
{
    private ListChangeRepositoryInterface&MockObject $changes;
    private ListRepositoryInterface&MockObject $lists;
    private TagRepositoryInterface&MockObject $tags;
    private ItemDefinitionRepositoryInterface&MockObject $items;
    private ListDetailCacheInterface&MockObject $cache;
    private PDO&MockObject $pdo;
    private AdminListChangeService $service;

    protected function setUp(): void
    {
        $this->changes = $this->createMock(ListChangeRepositoryInterface::class);
        $this->lists = $this->createMock(ListRepositoryInterface::class);
        $this->tags = $this->createMock(TagRepositoryInterface::class);
        $this->items = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $this->cache = $this->createMock(ListDetailCacheInterface::class);
        $this->pdo = $this->createMock(PDO::class);

        $this->pdo->method('inTransaction')->willReturn(false);
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);
        $this->pdo->method('rollBack')->willReturn(true);

        $this->service = new AdminListChangeService(
            $this->changes,
            $this->lists,
            $this->tags,
            $this->items,
            $this->cache,
            $this->pdo,
        );
    }

    public function testListChangesDefaultsToPending(): void
    {
        $expectedChange = $this->createChange(ListChange::STATUS_PENDING);

        $this->changes
            ->expects(self::once())
            ->method('findByStatus')
            ->with(ListChange::STATUS_PENDING)
            ->willReturn([$expectedChange]);

        $result = $this->service->listChanges(null);

        self::assertSame([$expectedChange], $result);
    }

    public function testListChangesRejectsInvalidStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->listChanges('unknown');
    }

    public function testApproveChangeAppliesTagAndInvalidatesCache(): void
    {
        $change = $this->createChange(ListChange::STATUS_PENDING, ListChange::TYPE_ADD_TAG, [
            'name' => 'Weapons',
            'color' => '#FFAA00',
        ]);

        $approved = $this->createChange(ListChange::STATUS_APPROVED, ListChange::TYPE_ADD_TAG, [
            'name' => 'Weapons',
            'color' => '#FFAA00',
        ], 'reviewer-1');

        $list = $this->createList();

        $this->changes
            ->expects(self::once())
            ->method('findPendingByIdForUpdate')
            ->with('change-1')
            ->willReturn($change);

        $this->lists
            ->expects(self::once())
            ->method('findById')
            ->with('list-1')
            ->willReturn($list);

        $this->tags
            ->expects(self::once())
            ->method('create')
            ->with('list-1', 'Weapons', '#FFAA00');

        $this->changes
            ->expects(self::once())
            ->method('markApproved')
            ->with('change-1', 'reviewer-1')
            ->willReturn($approved);

        $this->cache
            ->expects(self::once())
            ->method('invalidateListDetail')
            ->with('owner-1', 'list-1');

        $result = $this->service->approveChange('change-1', 'reviewer-1');

        self::assertSame($approved, $result);
    }

    public function testApproveChangeRejectsSelfApproval(): void
    {
        $change = $this->createChange(ListChange::STATUS_PENDING, ListChange::TYPE_ADD_TAG, [
            'name' => 'Weapons',
        ], actor: 'reviewer-1');

        $this->changes
            ->expects(self::once())
            ->method('findPendingByIdForUpdate')
            ->with('change-1')
            ->willReturn($change);

        $this->expectException(DomainException::class);

        $this->service->approveChange('change-1', 'reviewer-1');
    }

    public function testRejectChangeMarksRejected(): void
    {
        $change = $this->createChange(ListChange::STATUS_PENDING);
        $rejected = $this->createChange(ListChange::STATUS_REJECTED, reviewedBy: 'reviewer-1');

        $this->changes
            ->expects(self::once())
            ->method('findPendingByIdForUpdate')
            ->with('change-1')
            ->willReturn($change);

        $this->changes
            ->expects(self::once())
            ->method('markRejected')
            ->with('change-1', 'reviewer-1')
            ->willReturn($rejected);

        $result = $this->service->rejectChange('change-1', 'reviewer-1');

        self::assertSame($rejected, $result);
    }

    public function testApproveChangeAppliesItemUpdate(): void
    {
        $change = $this->createChange(ListChange::STATUS_PENDING, ListChange::TYPE_EDIT_ITEM, [
            'itemId' => 'item-1',
            'name' => 'New Sword',
            'description' => 'Updated description',
            'tagIds' => ['tag-1'],
        ]);

        $approved = $this->createChange(ListChange::STATUS_APPROVED, ListChange::TYPE_EDIT_ITEM, [
            'itemId' => 'item-1',
            'name' => 'New Sword',
        ], 'reviewer-1');

        $list = $this->createList();

        $this->changes
            ->expects(self::once())
            ->method('findPendingByIdForUpdate')
            ->with('change-2')
            ->willReturn($change);

        $this->lists
            ->expects(self::once())
            ->method('findById')
            ->with('list-1')
            ->willReturn($list);

        $this->items
            ->expects(self::once())
            ->method('update')
            ->with('item-1', 'list-1', [
                'name' => 'New Sword',
                'description' => 'Updated description',
                'tagIds' => ['tag-1'],
            ]);

        $this->changes
            ->expects(self::once())
            ->method('markApproved')
            ->with('change-2', 'reviewer-2')
            ->willReturn($approved);

        $this->cache
            ->expects(self::once())
            ->method('invalidateListDetail')
            ->with('owner-1', 'list-1');

        $result = $this->service->approveChange('change-2', 'reviewer-2');

        self::assertSame($approved, $result);
    }

    public function testApproveChangeValidatesExistingList(): void
    {
        $change = $this->createChange(ListChange::STATUS_PENDING, ListChange::TYPE_ADD_TAG, ['name' => 'Weapons']);

        $this->changes
            ->expects(self::once())
            ->method('findPendingByIdForUpdate')
            ->with('change-1')
            ->willReturn($change);

        $this->lists
            ->expects(self::once())
            ->method('findById')
            ->with('list-1')
            ->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List not found for change.');

        $this->service->approveChange('change-1', 'reviewer-1');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createChange(
        string $status,
        string $type = ListChange::TYPE_ADD_TAG,
        array $payload = ['name' => 'Weapons'],
        string $reviewedBy = null,
        string $actor = 'actor-1'
    ): ListChange {
        return new ListChange(
            'change-1',
            'list-1',
            $actor,
            $type,
            $payload,
            $status,
            new DateTimeImmutable('-1 minute'),
            $reviewedBy,
            $reviewedBy !== null ? new DateTimeImmutable() : null,
        );
    }

    private function createList(): GameList
    {
        return new GameList(
            'list-1',
            'owner-1',
            new Game('game-1', 'Game'),
            'My List',
            null,
            false,
            new DateTimeImmutable('-1 hour'),
        );
    }
}
