<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Admin;

use DateTimeImmutable;
use DomainException;
use GameItemsList\Application\Service\Admin\AdminListChangeService;
use GameItemsList\Application\Service\Lists\ListDetailCacheInterface;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use GameItemsList\Domain\Lists\Tag;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AdminListChangeServiceTest extends TestCase
{
    public function testListChangesDefaultsToPendingWhenStatusMissing(): void
    {
        $changes = new FakeListChangeRepository();
        $expected = [$this->createChange('change-1')];
        $changes->findByStatusResult = $expected;

        $service = $this->createService(changes: $changes);

        $result = $service->listChanges(null);

        self::assertSame($expected, $result);
        self::assertSame([ListChange::STATUS_PENDING], $changes->findByStatusCalls);
    }

    public function testListChangesReturnsAllWhenRequested(): void
    {
        $changes = new FakeListChangeRepository();
        $expected = [$this->createChange('change-2')];
        $changes->findByStatusResult = $expected;

        $service = $this->createService(changes: $changes);

        $result = $service->listChanges('all');

        self::assertSame($expected, $result);
        self::assertSame([null], $changes->findByStatusCalls);
    }

    public function testListChangesRejectsInvalidStatus(): void
    {
        $service = $this->createService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status filter.');

        $service->listChanges('invalid');
    }

    public function testApproveChangePersistsAddTagAndBustsCache(): void
    {
        $pending = $this->createChange(
            'change-3',
            listId: 'list-12',
            type: ListChange::TYPE_ADD_TAG,
            payload: ['name' => 'Support', 'color' => '#aabbcc'],
        );

        $approved = $this->createChange(
            'change-3',
            listId: 'list-12',
            status: ListChange::STATUS_APPROVED,
            reviewedBy: 'reviewer-9',
        );

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;
        $changes->approvedChange = $approved;

        $lists = new FakeListRepository();
        $list = $this->createList('list-12', 'owner-55');
        $lists->list = $list;

        $tags = new FakeTagRepository();
        $cache = new FakeListDetailCache();
        $pdo = $this->createPdo();

        $service = $this->createService(
            changes: $changes,
            lists: $lists,
            tags: $tags,
            cache: $cache,
            pdo: $pdo,
        );

        $result = $service->approveChange('change-3', 'reviewer-9');

        self::assertSame($approved, $result);
        self::assertSame([
            [
                'changeId' => 'change-3',
                'reviewerId' => 'reviewer-9',
            ],
        ], $changes->markApprovedCalls);
        self::assertSame([
            [
                'listId' => 'list-12',
                'name' => 'Support',
                'color' => '#aabbcc',
            ],
        ], $tags->createdTags);
        self::assertSame([
            [
                'accountId' => 'owner-55',
                'listId' => 'list-12',
            ],
        ], $cache->invalidations);
        self::assertFalse($pdo->inTransaction());
    }

    public function testApproveChangeThrowsWhenReviewerMatchesActor(): void
    {
        $pending = $this->createChange('change-4', actorAccountId: 'actor-1');

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;

        $lists = new FakeListRepository();
        $lists->list = $this->createList();

        $service = $this->createService(
            changes: $changes,
            lists: $lists,
            cache: new FakeListDetailCache(),
            pdo: $this->createPdo(),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Reviewers may not approve their own changes.');

        try {
            $service->approveChange('change-4', 'actor-1');
        } finally {
            self::assertSame([], $changes->markApprovedCalls);
        }
    }

    public function testApproveChangeThrowsWhenListMissing(): void
    {
        $pending = $this->createChange('change-5', listId: 'missing-list');

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;

        $lists = new FakeListRepository();
        $lists->list = null;

        $service = $this->createService(
            changes: $changes,
            lists: $lists,
            cache: new FakeListDetailCache(),
            pdo: $this->createPdo(),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List not found for change.');

        try {
            $service->approveChange('change-5', 'reviewer-1');
        } finally {
            self::assertSame([], $changes->markApprovedCalls);
        }
    }

    public function testApproveChangeRollsBackOnUnsupportedType(): void
    {
        $pending = $this->createChange(
            'change-6',
            type: ListChange::TYPE_REMOVE_ITEM,
        );

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;

        $lists = new FakeListRepository();
        $lists->list = $this->createList();

        $pdo = $this->createPdo();

        $service = $this->createService(
            changes: $changes,
            lists: $lists,
            cache: new FakeListDetailCache(),
            pdo: $pdo,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported change type: remove_item');

        try {
            $service->approveChange('change-6', 'reviewer-7');
        } finally {
            self::assertFalse($pdo->inTransaction());
        }
    }

    public function testApproveChangeNormalizesEditItemPayload(): void
    {
        $pending = $this->createChange(
            'change-7',
            type: ListChange::TYPE_EDIT_ITEM,
            payload: [
                'itemId' => 'item-55',
                'description' => null,
                'imageUrl' => 'https://example.test/image.png',
                'tagIds' => ['tag-1', 'tag-1'],
                'name' => 'Updated Name',
            ],
        );

        $approved = $this->createChange(
            'change-7',
            status: ListChange::STATUS_APPROVED,
            reviewedBy: 'reviewer-10',
        );

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;
        $changes->approvedChange = $approved;

        $lists = new FakeListRepository();
        $lists->list = $this->createList();

        $items = new FakeItemDefinitionRepository();
        $pdo = $this->createPdo();

        $service = $this->createService(
            changes: $changes,
            lists: $lists,
            items: $items,
            cache: new FakeListDetailCache(),
            pdo: $pdo,
        );

        $service->approveChange('change-7', 'reviewer-10');

        self::assertSame([
            [
                'itemId' => 'item-55',
                'listId' => 'list-1',
                'changes' => [
                    'description' => null,
                    'imageUrl' => 'https://example.test/image.png',
                    'tagIds' => ['tag-1'],
                    'name' => 'Updated Name',
                ],
            ],
        ], $items->updatedItems);
        self::assertFalse($pdo->inTransaction());
    }

    public function testApproveChangeValidatesEditItemTagIds(): void
    {
        $pending = $this->createChange(
            'change-8',
            type: ListChange::TYPE_EDIT_ITEM,
            payload: [
                'itemId' => 'item-9',
                'tagIds' => 'invalid',
            ],
        );

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;

        $lists = new FakeListRepository();
        $lists->list = $this->createList();

        $items = new FakeItemDefinitionRepository();
        $pdo = $this->createPdo();

        $service = $this->createService(
            changes: $changes,
            lists: $lists,
            items: $items,
            cache: new FakeListDetailCache(),
            pdo: $pdo,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tagIds must be an array.');

        try {
            $service->approveChange('change-8', 'reviewer-10');
        } finally {
            self::assertSame([], $items->updatedItems);
            self::assertFalse($pdo->inTransaction());
        }
    }

    public function testApproveChangeValidatesListMetadataPayload(): void
    {
        $pending = $this->createChange(
            'change-9',
            type: ListChange::TYPE_LIST_METADATA,
            payload: [],
        );

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;

        $lists = new FakeListRepository();
        $lists->list = $this->createList();

        $pdo = $this->createPdo();

        $service = $this->createService(
            changes: $changes,
            lists: $lists,
            cache: new FakeListDetailCache(),
            pdo: $pdo,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List metadata payload empty.');

        try {
            $service->approveChange('change-9', 'reviewer-2');
        } finally {
            self::assertSame([], $lists->metadataUpdates);
            self::assertFalse($pdo->inTransaction());
        }
    }

    public function testRejectChangePersistsAndReturnsChange(): void
    {
        $pending = $this->createChange('change-10');
        $rejected = $this->createChange(
            'change-10',
            status: ListChange::STATUS_REJECTED,
            reviewedBy: 'reviewer-3',
        );

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;
        $changes->rejectedChange = $rejected;

        $service = $this->createService(
            changes: $changes,
            cache: new FakeListDetailCache(),
            pdo: $this->createPdo(),
        );

        $result = $service->rejectChange('change-10', 'reviewer-3');

        self::assertSame($rejected, $result);
        self::assertSame([
            [
                'changeId' => 'change-10',
                'reviewerId' => 'reviewer-3',
            ],
        ], $changes->markRejectedCalls);
    }

    public function testRejectChangePreventsSelfReview(): void
    {
        $pending = $this->createChange('change-11', actorAccountId: 'actor-3');

        $changes = new FakeListChangeRepository();
        $changes->pendingChange = $pending;

        $service = $this->createService(
            changes: $changes,
            cache: new FakeListDetailCache(),
            pdo: $this->createPdo(),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Reviewers may not reject their own changes.');

        try {
            $service->rejectChange('change-11', 'actor-3');
        } finally {
            self::assertSame([], $changes->markRejectedCalls);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createChange(
        string $id,
        string $listId = 'list-1',
        string $actorAccountId = 'actor-1',
        string $type = ListChange::TYPE_ADD_ITEM,
        array $payload = ['name' => 'Example', 'storageType' => ItemDefinition::STORAGE_TEXT],
        string $status = ListChange::STATUS_PENDING,
        ?string $reviewedBy = null,
    ): ListChange {
        return new ListChange(
            $id,
            $listId,
            $actorAccountId,
            $type,
            $payload,
            $status,
            new DateTimeImmutable('2024-05-01T12:00:00Z'),
            $reviewedBy,
            $reviewedBy === null ? null : new DateTimeImmutable('2024-05-02T12:00:00Z'),
        );
    }

    private function createList(string $id = 'list-1', string $ownerAccountId = 'owner-1'): GameList
    {
        return new GameList(
            $id,
            $ownerAccountId,
            new Game('game-1', 'Game Name'),
            'List Name',
            null,
            false,
            new DateTimeImmutable('2024-04-01T00:00:00Z'),
        );
    }

    private function createService(
        ?FakeListChangeRepository $changes = null,
        ?FakeListRepository $lists = null,
        ?FakeTagRepository $tags = null,
        ?FakeItemDefinitionRepository $items = null,
        ?FakeListDetailCache $cache = null,
        ?PDO $pdo = null,
    ): AdminListChangeService {
        return new AdminListChangeService(
            $changes ?? new FakeListChangeRepository(),
            $lists ?? new FakeListRepository(),
            $tags ?? new FakeTagRepository(),
            $items ?? new FakeItemDefinitionRepository(),
            $cache ?? new FakeListDetailCache(),
            $pdo ?? $this->createPdo(),
        );
    }

    private function createPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
