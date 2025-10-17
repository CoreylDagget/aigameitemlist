<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Lists\Share;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ListShareService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShareListAction
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
        $listId = isset($args['listId']) ? (string) $args['listId'] : '';

        if ($listId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing listId parameter.');
        }

        $accountId = (string) $request->getAttribute('account_id');
        $data = (array) $request->getParsedBody();
        $rotate = false;

        if (array_key_exists('rotate', $data)) {
            $rotateValue = $data['rotate'];

            if (!is_bool($rotateValue)) {
                return $this->responder->problem(400, 'Invalid request', 'rotate must be a boolean.');
            }

            $rotate = $rotateValue;
        }

        try {
            $share = $this->shareService->shareList($accountId, $listId, $rotate);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        }

        $shareUrl = $this->buildShareUrl($request, $share->token());

        return $this->responder->respond([
            'active' => true,
            'token' => $share->token(),
            'shareUrl' => $shareUrl,
            'createdAt' => $share->createdAt()->format(DATE_ATOM),
        ], 201);
    }

    private function buildShareUrl(ServerRequestInterface $request, string $token): string
    {
        $uri = $request->getUri()
            ->withPath('/v1/shared/' . $token)
            ->withQuery('')
            ->withFragment('');

        return (string) $uri;
    }
}
