<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

use DateTimeImmutable;

final class ItemEntry
{
    public function __construct(
        private readonly string $id,
        private readonly string $listId,
        private readonly string $itemDefinitionId,
        private readonly string $accountId,
        private readonly bool|int|string $value,
        private readonly DateTimeImmutable $updatedAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function listId(): string
    {
        return $this->listId;
    }

    public function itemDefinitionId(): string
    {
        return $this->itemDefinitionId;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function value(): bool|int|string
    {
        return $this->value;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        if ($row['value_boolean'] !== null) {
            $value = (bool) $row['value_boolean'];
        } elseif ($row['value_integer'] !== null) {
            $value = (int) $row['value_integer'];
        } else {
            $value = (string) $row['value_text'];
        }

        return new self(
            $row['id'],
            $row['list_id'],
            $row['item_definition_id'],
            $row['account_id'],
            $value,
            new DateTimeImmutable($row['updated_at']),
        );
    }
}

