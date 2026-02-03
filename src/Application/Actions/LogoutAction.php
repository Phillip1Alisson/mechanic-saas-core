<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LogoutAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso.',
            'data' => null,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
