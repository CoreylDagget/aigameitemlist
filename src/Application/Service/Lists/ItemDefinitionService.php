<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use InvalidArgumentException;

final class ItemDefinitionService
{
    /** @var string[] */
    private const ALLOWED_STORAGE_TYPES = [
        ItemDefinition::STORAGE_BOOLEAN,
        ItemDefinition::STORAGE_COUNT,
        ItemDefinition::STORAGE_TEXT,
    ];

    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly ItemDefinitionRepositoryInterface $items,
        private readonly TagRepositoryInterface $tags,
        private readonly ListChangeRepositoryInterface $listChanges,
    ) {
    }

    /**
     * @return ItemDefinition[]
     */
    public function listItems(
        string $accountId,
        string $listId,
        ?string $tagId = null,
        ?bool $owned = null,
        ?string $search = null,
    ): array {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to view this list.');

        $accountFilter = $owned !== null ? $accountId : null;

        return $this->items->findByList($listId, $accountFilter, $tagId, $owned, $search);
    }

    /**
     * @param string[] $tagIds
     */
    public function proposeCreateItem(
        string $accountId,
        string $listId,
        string $name,
        string $storageType,
        ?string $description,
        ?string $imageUrl,
        array $tagIds,
    ): ListChange {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to modify this list.');

        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new InvalidArgumentException('Item name must be provided.');
        }

        $storageType = strtolower($storageType);

        if (!in_array($storageType, self::ALLOWED_STORAGE_TYPES, true)) {
            throw new InvalidArgumentException('Invalid storageType supplied.');
        }

        $normalizedDescription = null;

        if ($description !== null) {
            $trimmed = trim($description);
            $normalizedDescription = $trimmed === '' ? null : $trimmed;
        }

        $normalizedImageUrl = null;

        if ($imageUrl !== null) {
            $imageUrl = trim($imageUrl);

            if ($imageUrl === '' || filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException('imageUrl must be a valid URI.');
            }

            $normalizedImageUrl = $imageUrl;
        }

        $normalizedTagIds = $this->normalizeTagIds($listId, $tagIds);

        $payload = [
            'name' => $normalizedName,
            'storageType' => $storageType,
            'tagIds' => $normalizedTagIds,
        ];

        if ($normalizedDescription !== null) {
            $payload['description'] = $normalizedDescription;
        }

        if ($normalizedImageUrl !== null) {
            $payload['imageUrl'] = $normalizedImageUrl;
        }

        return $this->listChanges->create(
            $listId,
            $accountId,
            ListChange::TYPE_ADD_ITEM,
            $payload,
        );
    }

    /**
     * @param array{
     *     name?: mixed,
     *     description?: mixed,
     *     imageUrl?: mixed,
     *     storageType?: mixed,
     *     tagIds?: mixed,
     * } $changes
     */
    public function proposeUpdateItem(
        string $accountId,
        string $listId,
        string $itemId,
        array $changes
    ): ListChange {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to modify this list.');

        $item = $this->items->findByIdForList($itemId, $listId);

        if ($item === null) {
            throw new InvalidArgumentException('Item not found');
        }

        $payload = [];

        if (array_key_exists('name', $changes)) {
            $name = trim((string) $changes['name']);

            if ($name === '') {
                throw new InvalidArgumentException('Item name must be provided.');
            }

            if ($name !== $item->name()) {
                $payload['name'] = $name;
            }
        }

        if (array_key_exists('description', $changes)) {
            $descriptionValue = $changes['description'];

            if ($descriptionValue !== null && !is_string($descriptionValue)) {
                throw new InvalidArgumentException('description must be a string or null.');
            }

            $normalizedDescription = null;

            if ($descriptionValue !== null) {
                $trimmed = trim($descriptionValue);
                $normalizedDescription = $trimmed === '' ? null : $trimmed;
            }

            if ($normalizedDescription !== $item->description()) {
                $payload['description'] = $normalizedDescription;
            }
        }

        if (array_key_exists('imageUrl', $changes)) {
            $imageValue = $changes['imageUrl'];

            if ($imageValue !== null && !is_string($imageValue)) {
                throw new InvalidArgumentException('imageUrl must be a string or null.');
            }

            $normalizedImageUrl = null;

            if ($imageValue !== null) {
                $imageValue = trim($imageValue);

                if ($imageValue === '' || filter_var($imageValue, FILTER_VALIDATE_URL) === false) {
                    throw new InvalidArgumentException('imageUrl must be a valid URI.');
                }

                $normalizedImageUrl = $imageValue;
            }

            if ($normalizedImageUrl !== $item->imageUrl()) {
                $payload['imageUrl'] = $normalizedImageUrl;
            }
        }

        if (array_key_exists('storageType', $changes)) {
            $storageType = strtolower((string) $changes['storageType']);

            if (!in_array($storageType, self::ALLOWED_STORAGE_TYPES, true)) {
                throw new InvalidArgumentException('Invalid storageType supplied.');
            }

            if ($storageType !== $item->storageType()) {
                $payload['storageType'] = $storageType;
            }
        }

        if (array_key_exists('tagIds', $changes)) {
            $tagIdsValue = $changes['tagIds'];

            if ($tagIdsValue !== null && !is_array($tagIdsValue)) {
                throw new InvalidArgumentException('tagIds must be an array of identifiers.');
            }

            /** @var array<int, string>|null $tagIdsValue */
            $normalizedTagIds = $this->normalizeTagIds($listId, $tagIdsValue ?? []);
            $existingTagIds = array_map(static fn ($tag) => $tag->id(), $item->tags());

            sort($normalizedTagIds);
            $sortedExisting = $existingTagIds;
            sort($sortedExisting);

            if ($normalizedTagIds !== $sortedExisting) {
                $payload['tagIds'] = $normalizedTagIds;
            }
        }

        if ($payload === []) {
            throw new InvalidArgumentException('No valid changes provided.');
        }

        $payload['itemId'] = $itemId;

        return $this->listChanges->create(
            $listId,
            $accountId,
            ListChange::TYPE_EDIT_ITEM,
            $payload,
        );
    }

    /**
     * @param string[] $tagIds
     * @return string[]
     */
    private function normalizeTagIds(string $listId, array $tagIds): array
    {
        $normalized = [];

        foreach ($tagIds as $tagId) {
            if (!is_string($tagId)) {
                throw new InvalidArgumentException('tagIds must be strings.');
            }

            $normalized[] = $tagId;
        }

        $normalized = array_values(array_unique($normalized));

        if ($normalized === []) {
            return [];
        }

        $tags = $this->tags->findByIds($listId, $normalized);

        if (count($tags) !== count($normalized)) {
            throw new InvalidArgumentException('One or more tagIds are invalid for this list.');
        }

        return $normalized;
    }
}
