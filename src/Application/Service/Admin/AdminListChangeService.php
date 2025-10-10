<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Admin;

use DomainException;
use GameItemsList\Application\Service\Lists\ListDetailCacheInterface;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use InvalidArgumentException;
use PDO;
use Throwable;

final class AdminListChangeService
{
    private const ALLOWED_STATUSES = [
        ListChange::STATUS_PENDING,
        ListChange::STATUS_APPROVED,
        ListChange::STATUS_REJECTED,
    ];

    public function __construct(
        private readonly ListChangeRepositoryInterface $changes,
        private readonly ListRepositoryInterface $lists,
        private readonly TagRepositoryInterface $tags,
        private readonly ItemDefinitionRepositoryInterface $items,
        private readonly ListDetailCacheInterface $listDetailCache,
        private readonly PDO $pdo,
    ) {
    }

    /**
     * @return ListChange[]
     */
    public function listChanges(?string $status): array
    {
        if ($status === null || $status === '') {
            $status = ListChange::STATUS_PENDING;
        }

        if ($status === 'all') {
            return $this->changes->findByStatus();
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status filter.');
        }

        return $this->changes->findByStatus($status);
    }

    public function approveChange(string $changeId, string $reviewerAccountId): ListChange
    {
        $this->beginTransaction();

        try {
            $change = $this->changes->findPendingByIdForUpdate($changeId);

            if ($change === null) {
                throw new InvalidArgumentException('Change not found.');
            }

            if ($change->actorAccountId() === $reviewerAccountId) {
                throw new DomainException('Reviewers may not approve their own changes.');
            }

            $list = $this->requireList($change->listId());

            $this->applyChange($change);

            $approved = $this->changes->markApproved($changeId, $reviewerAccountId);

            $this->pdo->commit();

            $this->listDetailCache->invalidateListDetail($list->ownerAccountId(), $list->id());

            return $approved;
        } catch (Throwable $exception) {
            $this->rollbackSilently();

            throw $exception;
        }
    }

    public function rejectChange(string $changeId, string $reviewerAccountId): ListChange
    {
        $this->beginTransaction();

        try {
            $change = $this->changes->findPendingByIdForUpdate($changeId);

            if ($change === null) {
                throw new InvalidArgumentException('Change not found.');
            }

            if ($change->actorAccountId() === $reviewerAccountId) {
                throw new DomainException('Reviewers may not reject their own changes.');
            }

            $rejected = $this->changes->markRejected($changeId, $reviewerAccountId);

            $this->pdo->commit();

            return $rejected;
        } catch (Throwable $exception) {
            $this->rollbackSilently();

            throw $exception;
        }
    }

    private function applyChange(ListChange $change): void
    {
        $payload = $change->payload();

        switch ($change->type()) {
            case ListChange::TYPE_ADD_TAG:
                $this->applyAddTag($change->listId(), $payload);

                return;
            case ListChange::TYPE_ADD_ITEM:
                $this->applyAddItem($change->listId(), $payload);

                return;
            case ListChange::TYPE_EDIT_ITEM:
                $this->applyEditItem($change->listId(), $payload);

                return;
            case ListChange::TYPE_LIST_METADATA:
                $this->applyListMetadata($change->listId(), $payload);

                return;
        }

        throw new InvalidArgumentException(sprintf('Unsupported change type: %s', $change->type()));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyAddTag(string $listId, array $payload): void
    {
        $name = $this->requireString($payload, 'name', 'Tag payload missing name.');
        $color = null;

        if (array_key_exists('color', $payload)) {
            $colorValue = $payload['color'];

            if ($colorValue !== null && !is_string($colorValue)) {
                throw new InvalidArgumentException('Tag color must be null or string.');
            }

            $color = $colorValue;
        }

        $this->tags->create($listId, $name, $color);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyAddItem(string $listId, array $payload): void
    {
        $name = $this->requireString($payload, 'name', 'Item payload missing name.');
        $storageType = $this->requireString($payload, 'storageType', 'Item payload missing storageType.');
        $description = $this->nullableString($payload['description'] ?? null, 'description');
        $imageUrl = $this->nullableString($payload['imageUrl'] ?? null, 'imageUrl');
        $tagIds = $this->normalizeTagIds($payload['tagIds'] ?? []);

        $this->items->create($listId, $name, $description, $imageUrl, $storageType, $tagIds);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyEditItem(string $listId, array $payload): void
    {
        $itemId = $this->requireString($payload, 'itemId', 'Item update payload missing itemId.');

        $changes = $payload;
        unset($changes['itemId']);

        if ($changes === []) {
            return;
        }

        if (array_key_exists('description', $changes)) {
            $changes['description'] = $this->nullableString($changes['description'], 'description');
        }

        if (array_key_exists('imageUrl', $changes)) {
            $changes['imageUrl'] = $this->nullableString($changes['imageUrl'], 'imageUrl');
        }

        if (array_key_exists('tagIds', $changes)) {
            $changes['tagIds'] = $this->normalizeTagIds($changes['tagIds']);
        }

        $this->items->update($itemId, $listId, $changes);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyListMetadata(string $listId, array $payload): void
    {
        $changes = [];

        if (array_key_exists('name', $payload)) {
            $changes['name'] = $this->requireString($payload, 'name', 'List metadata missing name.');
        }

        if (array_key_exists('description', $payload)) {
            $changes['description'] = $this->nullableString($payload['description'], 'description');
        }

        if ($changes === []) {
            throw new InvalidArgumentException('List metadata payload empty.');
        }

        $this->lists->updateMetadata($listId, $changes);
    }

    private function requireList(string $listId): GameList
    {
        $list = $this->lists->findById($listId);

        if ($list === null) {
            throw new InvalidArgumentException('List not found for change.');
        }

        return $list;
    }

    private function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    private function rollbackSilently(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireString(array $payload, string $key, string $errorMessage): string
    {
        $value = $payload[$key] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException($errorMessage);
        }

        return $value;
    }

    private function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s must be a string or null.', $field));
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    private function normalizeTagIds(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('tagIds must be an array.');
        }

        $normalized = [];

        foreach ($value as $tagId) {
            if (!is_string($tagId)) {
                throw new InvalidArgumentException('tagIds must be strings.');
            }

            $normalized[] = $tagId;
        }

        return array_values(array_unique($normalized));
    }
}
