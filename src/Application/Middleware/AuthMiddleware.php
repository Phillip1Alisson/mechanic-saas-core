<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthService $authService)
    {
    }

    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
            $token = trim($m[1]);
        }

        if ($token === null || $token === '') {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Token de autenticação ausente ou inválido.',
                'data' => null,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $userId = $this->authService->validateToken($token);
        if ($userId === null) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Token inválido ou expirado.',
                'data' => null,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $request = $request->withAttribute('user_id', $userId);
        return $handler->handle($request);
    }
}
