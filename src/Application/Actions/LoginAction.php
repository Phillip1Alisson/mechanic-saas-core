<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Request\LoginRequestValidator;
use App\Domain\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
final class LoginAction
{
    public function __construct(
        private AuthService $authService,
        private LoginRequestValidator $validator,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $result = $this->validator->validate($request);
        if (!$result['valid']) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Dados inválidos.',
                'data' => ['errors' => $result['errors']],
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $token = $this->authService->login($result['email'], $result['password']);
        if ($token === null) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'E-mail ou senha inválidos.',
                'data' => null,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Login realizado com sucesso.',
            'data' => ['token' => $token],
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
