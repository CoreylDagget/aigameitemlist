<?php
declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

use DateTimeImmutable;

final class ListChange
{
    public const TYPE_ADD_ITEM = 'add_item';
    public const TYPE_EDIT_ITEM = 'edit_item';
    public const TYPE_REMOVE_ITEM = 'remove_item';
    public const TYPE_ADD_TAG = 'add_tag';
    public const TYPE_EDIT_TAG = 'edit_tag';
    public const TYPE_REMOVE_TAG = 'remove_tag';
    public const TYPE_LIST_METADATA = 'list_metadata';
    public const TYPE_PUBLISH_TOGGLE = 'publish_toggle';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $id,
        private readonly string $listId,
        private readonly string $actorAccountId,
        private readonly string $type,
        private readonly array $payload,
        private readonly string $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly ?string $reviewedBy = null,
        private readonly ?DateTimeImmutable $reviewedAt = null,
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

    public function actorAccountId(): string
    {
        return $this->actorAccountId;
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function reviewedBy(): ?string
    {
        return $this->reviewedBy;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        $payload = $row['payload'];

        if (is_string($payload)) {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            $payload = $decoded;
        }

        if (!is_array($payload)) {
            $payload = [];
        }

        return new self(
            $row['id'],
            $row['list_id'],
            $row['actor_account_id'],
            $row['type'],
            $payload,
            $row['status'],
            new DateTimeImmutable($row['created_at']),
            $row['reviewed_by'] ?? null,
            isset($row['reviewed_at']) && $row['reviewed_at'] !== null
                ? new DateTimeImmutable($row['reviewed_at'])
                : null,
        );
    }
}
