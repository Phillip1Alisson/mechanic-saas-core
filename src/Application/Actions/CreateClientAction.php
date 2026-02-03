<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Request\ClientRequestValidator;
use App\Domain\Exception\DocumentAlreadyExistsException;
use App\Domain\Services\ClientService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateClientAction
{
    public function __construct(
        private ClientService $clientService,
        private ClientRequestValidator $validator,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $result = $this->validator->validateCreate($request);
        if (!$result['valid']) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Dados inválidos.',
                'data' => ['errors' => $result['errors']],
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $d = $result['data'];
        try {
            $client = $this->clientService->create($d['name'], $d['phone'], $d['type'], $d['document']);
        } catch (DocumentAlreadyExistsException) {
            $msg = $d['type'] === 'PF' ? 'CPF já existente.' : 'CNPJ já existente.';
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $msg,
                'data' => null,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Cliente criado com sucesso.',
            'data' => $client->toArray(),
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
