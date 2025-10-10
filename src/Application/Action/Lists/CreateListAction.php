<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Lists;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ListServiceInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateListAction
{
    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly JsonResponder $responder
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $gameId = isset($data['gameId']) ? (string) $data['gameId'] : '';
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $isPublished = isset($data['isPublished']) ? (bool) $data['isPublished'] : false;

        $errors = [];

        if ($gameId === '') {
            $errors['gameId'] = 'gameId is required.';
        }

        if ($name === '') {
            $errors['name'] = 'name is required.';
        }

        if ($errors !== []) {
            return $this->responder->problem(400, 'Invalid request', 'Validation failed.', additional: ['errors' => $errors]);
        }

        $accountId = (string) $request->getAttribute('account_id');

        try {
            $list = $this->listService->createList($accountId, $gameId, $name, $description, $isPublished);
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        }

        return $this->responder->respond([
            'id' => $list->id(),
            'ownerAccountId' => $list->ownerAccountId(),
            'game' => [
                'id' => $list->game()->id(),
                'name' => $list->game()->name(),
            ],
            'name' => $list->name(),
            'description' => $list->description(),
            'isPublished' => $list->isPublished(),
            'createdAt' => $list->createdAt()->format(DATE_ATOM),
            'tags' => [],
            'items' => [],
        ], 201);
    }
}
