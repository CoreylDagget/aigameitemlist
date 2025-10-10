<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Auth;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Auth\AuthenticateAccountService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class LoginAction
{
    public function __construct(
        private readonly AuthenticateAccountService $authenticateAccountService,
        private readonly JsonResponder $responder
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        $password = isset($data['password']) ? (string) $data['password'] : '';

        if ($email === '' || $password === '') {
            return $this->responder->problem(400, 'Invalid request', 'Email and password are required.');
        }

        try {
            $result = $this->authenticateAccountService->authenticate($email, $password);
        } catch (RuntimeException $exception) {
            return $this->responder->problem(401, 'Unauthorized', 'Invalid credentials.');
        }

        $tokens = [
            'accessToken' => $result->accessToken(),
            'expiresIn' => $result->expiresIn(),
        ];

        if ($result->refreshToken() !== null) {
            $tokens['refreshToken'] = $result->refreshToken();
        }

        return $this->responder->respond([
            'account' => [
                'id' => $result->account()->id(),
                'email' => $result->account()->email(),
                'createdAt' => $result->account()->createdAt()->format(DATE_ATOM),
            ],
            'tokens' => $tokens,
        ]);
    }
}
