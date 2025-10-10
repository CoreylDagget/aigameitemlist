<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ItemEntry;
use GameItemsList\Domain\Lists\ItemEntryRepositoryInterface;
use InvalidArgumentException;

final class ItemEntryService
{
    public function __construct(
        private readonly ListService $listService,
        private readonly ItemEntryRepositoryInterface $entries,
        private readonly ItemDefinitionRepositoryInterface $itemDefinitions,
    ) {
    }

    /**
     * @return ItemEntry[]
     */
    public function listEntries(string $accountId, string $listId): array
    {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to view this list.');

        return $this->entries->findByListAndAccount($listId, $accountId);
    }

    public function setEntry(string $accountId, string $listId, string $itemId, mixed $value): ItemEntry
    {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to modify this list.');

        $item = $this->itemDefinitions->findByIdForList($itemId, $listId);

        if ($item === null) {
            throw new InvalidArgumentException('Item not found');
        }

        $normalizedValue = $this->normalizeValue($item->storageType(), $value);

        return $this->entries->upsert($listId, $itemId, $accountId, $normalizedValue, $item->storageType());
    }

    private function normalizeValue(string $storageType, mixed $value): bool|int|string
    {
        return match ($storageType) {
            ItemDefinition::STORAGE_BOOLEAN => $this->normalizeBoolean($value),
            ItemDefinition::STORAGE_COUNT => $this->normalizeCount($value),
            ItemDefinition::STORAGE_TEXT => $this->normalizeText($value),
            default => throw new InvalidArgumentException('Unsupported storage type for entries.'),
        };
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException('Value must be a boolean for this item.');
        }

        return $value;
    }

    private function normalizeCount(mixed $value): int
    {
        if (is_int($value)) {
            $normalized = $value;
        } elseif (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            $normalized = (int) $value;
        } else {
            throw new InvalidArgumentException('Value must be an integer for this item.');
        }

        if ($normalized < 0) {
            throw new InvalidArgumentException('Value must be zero or greater for count storage.');
        }

        return $normalized;
    }

    private function normalizeText(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Value must be a string for this item.');
        }

        return $value;
    }
}

