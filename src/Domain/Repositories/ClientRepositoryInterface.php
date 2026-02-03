<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Client;

interface ClientRepositoryInterface
{
    public function findById(int $id): ?Client;

    /** @return array{items: Client[], total: int} */
    public function findAll(int $page = 1, int $perPage = 10): array;

    public function save(Client $client): Client;

    public function delete(int $id): bool;
}
