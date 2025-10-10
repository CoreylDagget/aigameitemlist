<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

final class ItemDefinition
{
    public const STORAGE_BOOLEAN = 'boolean';
    public const STORAGE_COUNT = 'count';
    public const STORAGE_TEXT = 'text';

    /**
     * @param Tag[] $tags
     */
    public function __construct(
        private readonly string $id,
        private readonly string $listId,
        private readonly string $name,
        private readonly ?string $description,
        private readonly ?string $imageUrl,
        private readonly string $storageType,
        private readonly array $tags,
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

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function imageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function storageType(): string
    {
        return $this->storageType;
    }

    /**
     * @return Tag[]
     */
    public function tags(): array
    {
        return $this->tags;
    }

    /**
     * @param array<string, mixed> $row
     * @param Tag[]                 $tags
     */
    public static function fromDatabaseRow(array $row, array $tags = []): self
    {
        return new self(
            $row['id'],
            $row['list_id'],
            $row['name'],
            $row['description'] ?? null,
            $row['image_url'] ?? null,
            $row['storage_type'],
            $tags,
        );
    }
}
