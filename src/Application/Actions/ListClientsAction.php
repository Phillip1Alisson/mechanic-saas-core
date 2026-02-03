<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\Services\ClientService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListClientsAction
{
    public function __construct(private ClientService $clientService)
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 10;

        $result = $this->clientService->list($page, $perPage);
        $items = array_map(fn ($c) => $c->toArray(), $result['items']);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => null,
            'data' => [
                'items' => $items,
                'total' => $result['total'],
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
