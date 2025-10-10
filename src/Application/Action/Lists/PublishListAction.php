<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Lists;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ListService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PublishListAction
{
    public function __construct(
        private readonly ListService $listService,
        private readonly JsonResponder $responder
    ) {
    }

    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $listId = isset($args['listId']) ? (string) $args['listId'] : '';

        if ($listId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing listId parameter.');
        }

        $accountId = (string) $request->getAttribute('account_id');

        try {
            $list = $this->listService->publishList($accountId, $listId);
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
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
        ]);
    }
}
