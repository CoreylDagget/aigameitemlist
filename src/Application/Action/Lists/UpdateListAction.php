<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Lists;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ListServiceInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateListAction
{
    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly JsonResponder $responder
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
        $payload = [];
        $errors = [];

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);

            if ($name === '') {
                $errors['name'] = 'name must be at least 1 character.';
            } else {
                $payload['name'] = $name;
            }
        }

        if (array_key_exists('description', $data)) {
            $descriptionValue = $data['description'];

            if ($descriptionValue !== null && !is_string($descriptionValue)) {
                $errors['description'] = 'description must be a string or null.';
            } else {
                $payload['description'] = $descriptionValue === null ? null : (string) $descriptionValue;
            }
        }

        if ($payload === [] && $errors === []) {
            $errors['body'] = 'At least one of name or description must be provided.';
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
            $change = $this->listService->proposeMetadataUpdate($accountId, $listId, $payload);
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'List not found') {
                return $this->responder->problem(404, 'Not Found', $exception->getMessage());
            }

            return $this->responder->problem(400, 'Invalid request', $exception->getMessage());
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        }

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
        ], 202);
    }
}

