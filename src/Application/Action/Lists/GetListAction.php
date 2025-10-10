<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Lists;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ListDetailCacheInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetListAction
{
    public function __construct(
        private readonly ListDetailCacheInterface $listDetailCache,
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
            $detail = $this->listDetailCache->getListDetail($accountId, $listId);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        }

        return $this->responder->respond($detail);
    }
}
