<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Request\ClientRequestValidator;
use App\Domain\Exception\DocumentAlreadyExistsException;
use App\Domain\Services\ClientService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UpdateClientAction
{
    public function __construct(
        private ClientService $clientService,
        private ClientRequestValidator $validator,
    ) {
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

        $result = $this->validator->validateUpdate($request);
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
            $client = $this->clientService->update($id, $d['name'], $d['phone'], $d['type'], $d['document']);
        } catch (DocumentAlreadyExistsException) {
            $msg = $d['type'] === 'PF' ? 'CPF já existente.' : 'CNPJ já existente.';
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $msg,
                'data' => null,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }
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
            'message' => 'Cliente atualizado com sucesso.',
            'data' => $client->toArray(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
