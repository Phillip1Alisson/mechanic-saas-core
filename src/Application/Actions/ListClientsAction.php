<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Request\ClientListCriteriaConfig;
use App\Application\Request\ListCriteriaParser;
use App\Domain\Services\ClientService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListClientsAction
{
    public function __construct(
        private ClientService $clientService,
        private ListCriteriaParser $criteriaParser,
        private ClientListCriteriaConfig $criteriaConfig,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $criteria = ($this->criteriaParser)($request, $this->criteriaConfig);
        $result = $this->clientService->list($criteria);

        $items = array_map(fn ($c) => $c->toArray(), $result->getItems());
        $data = $result->toArray();
        $data['items'] = $items;

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => null,
            'data' => $data,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
