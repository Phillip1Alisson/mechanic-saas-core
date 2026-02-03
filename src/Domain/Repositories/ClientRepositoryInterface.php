<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Common\ListCriteria;
use App\Domain\Common\ListResult;
use App\Domain\Models\Client;

interface ClientRepositoryInterface
{
    public function findById(int $id): ?Client;

    /** Busca cliente por CPF/CNPJ. Se $excludeId for informado, ignora o cliente com esse ID (útil na atualização). */
    public function findByDocument(string $document, ?int $excludeId = null): ?Client;

    public function findAll(ListCriteria $criteria): ListResult;

    public function save(Client $client): Client;

    public function delete(int $id): bool;
}
