<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Models\Client;
use App\Domain\Repositories\ClientRepositoryInterface;

final class ClientService
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {
    }

    public function getById(int $id): ?Client
    {
        return $this->clientRepository->findById($id);
    }

    /** @return array{items: Client[], total: int} */
    public function list(int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = min(max(1, $perPage), 100);
        return $this->clientRepository->findAll($page, $perPage);
    }

    public function create(string $name, string $phone, string $type, string $document): Client
    {
        $client = new Client(null, $name, $phone, $type, $document);
        return $this->clientRepository->save($client);
    }

    public function update(int $id, string $name, string $phone, string $type, string $document): ?Client
    {
        $client = $this->clientRepository->findById($id);
        if ($client === null) {
            return null;
        }
        $updated = new Client(
            $client->getId(),
            $name,
            $phone,
            $type,
            $document,
            $client->getCreatedAt(),
            new \DateTimeImmutable(),
        );
        return $this->clientRepository->save($updated);
    }

    public function delete(int $id): bool
    {
        return $this->clientRepository->delete($id);
    }
}
