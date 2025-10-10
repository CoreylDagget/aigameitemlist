<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Items;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ItemDefinitionService;
use GameItemsList\Domain\Lists\ListChange;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateItemAction
{
    public function __construct(
        private readonly ItemDefinitionService $itemService,
        private readonly JsonResponder $responder,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $listId = isset($args['listId']) ? (string) $args['listId'] : '';

        if ($listId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing listId parameter.');
        }

        $data = (array) $request->getParsedBody();
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $storageType = isset($data['storageType']) ? (string) $data['storageType'] : '';
        $errors = [];

        $description = null;

        if (array_key_exists('description', $data)) {
            $descriptionValue = $data['description'];

            if ($descriptionValue !== null && !is_string($descriptionValue)) {
                $errors['description'] = 'description must be a string.';
            } elseif (is_string($descriptionValue)) {
                $description = $descriptionValue;
            }
        }

        $imageUrl = null;

        if (array_key_exists('imageUrl', $data)) {
            $imageUrlValue = $data['imageUrl'];

            if ($imageUrlValue !== null && !is_string($imageUrlValue)) {
                $errors['imageUrl'] = 'imageUrl must be a string.';
            } elseif (is_string($imageUrlValue)) {
                $imageUrl = $imageUrlValue;
            }
        }

        $tagIdsRaw = $data['tagIds'] ?? [];

        if ($name === '') {
            $errors['name'] = 'name is required.';
        }

        if ($storageType === '') {
            $errors['storageType'] = 'storageType is required.';
        }

        $tagIds = [];

        if ($tagIdsRaw !== [] && $tagIdsRaw !== null) {
            if (!is_array($tagIdsRaw)) {
                $errors['tagIds'] = 'tagIds must be an array of identifiers.';
            } else {
                foreach ($tagIdsRaw as $tagId) {
                    if (!is_string($tagId)) {
                        $errors['tagIds'] = 'tagIds must be an array of identifiers.';
                        break;
                    }

                    $tagIds[] = $tagId;
                }
            }
        }

        if ($errors !== []) {
            return $this->responder->problem(
                400,
                'Invalid request',
                'Validation failed.',
                additional: ['errors' => $errors]
            );
        }

        $accountId = (string) $request->getAttribute('account_id');

        try {
            $change = $this->itemService->proposeCreateItem(
                $accountId,
                $listId,
                $name,
                $storageType,
                $description,
                $imageUrl,
                $tagIds,
            );
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'List not found') {
                return $this->responder->problem(404, 'Not Found', $exception->getMessage());
            }

            return $this->responder->problem(400, 'Invalid request', $exception->getMessage());
        }

        return $this->respondWithChange($change, 202);
    }

    private function respondWithChange(ListChange $change, int $status): ResponseInterface
    {
        return $this->responder->respond([
            'id' => $change->id(),
            'listId' => $change->listId(),
            'actorAccountId' => $change->actorAccountId(),
            'type' => $change->type(),
            'payload' => $change->payload(),
            'status' => $change->status(),
            'reviewedBy' => $change->reviewedBy(),
            'reviewedAt' => $change->reviewedAt()?->format(DATE_ATOM),
            'createdAt' => $change->createdAt()->format(DATE_ATOM),
        ], $status);
    }
}
