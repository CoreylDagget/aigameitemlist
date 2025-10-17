<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Lists\Share;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ListShareService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetSharedListAction
{
    public function __construct(
        private readonly ListShareService $shareService,
        private readonly JsonResponder $responder,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $token = isset($args['token']) ? (string) $args['token'] : '';

        if ($token === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing token parameter.');
        }

        try {
            $detail = $this->shareService->getSharedList($token);
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        } catch (DomainException $exception) {
            return $this->responder->problem(410, 'Gone', $exception->getMessage());
        }

        return $this->responder->respond($detail);
    }
}
