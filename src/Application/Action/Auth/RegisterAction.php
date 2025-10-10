<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Auth;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Auth\RegisterAccountService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RegisterAction
{
    public function __construct(
        private readonly RegisterAccountService $registerAccountService,
        private readonly JsonResponder $responder
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        $password = isset($data['password']) ? (string) $data['password'] : '';

        $errors = [];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'A valid email address is required.';
        }

        if ($password === '' || strlen($password) < 12) {
            $errors['password'] = 'Password must be at least 12 characters long.';
        }

        if ($errors !== []) {
            return $this->responder->problem(400, 'Invalid request', 'Validation failed.', additional: ['errors' => $errors]);
        }

        try {
            $result = $this->registerAccountService->register($email, $password);
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Account already exists') {
                return $this->responder->problem(409, 'Conflict', 'Account already exists.');
            }

            return $this->responder->problem(400, 'Invalid request', $exception->getMessage());
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
        ], 201);
    }
}
