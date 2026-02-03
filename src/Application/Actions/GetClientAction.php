<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\Services\ClientService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetClientAction
{
    public function __construct(private ClientService $clientService)
    {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'ID inválido.',
                'data' => null,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $client = $this->clientService->getById($id);
        if ($client === null) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Cliente não encontrado.',
                'data' => null,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => null,
            'data' => $client->toArray(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
