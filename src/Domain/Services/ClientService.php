<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Common\ListCriteria;
use App\Domain\Common\ListResult;
use App\Domain\Exception\DocumentAlreadyExistsException;
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

    public function list(ListCriteria $criteria): ListResult
    {
        return $this->clientRepository->findAll($criteria);
    }

    public function create(string $name, string $phone, string $type, string $document): Client
    {
        if ($this->clientRepository->findByDocument($document) !== null) {
            throw new DocumentAlreadyExistsException();
        }
        $client = new Client(null, $name, $phone, $type, $document);
        return $this->clientRepository->save($client);
    }

    public function update(int $id, string $name, string $phone, string $type, string $document): ?Client
    {
        $client = $this->clientRepository->findById($id);
        if ($client === null) {
            return null;
        }
        if ($this->clientRepository->findByDocument($document, $id) !== null) {
            throw new DocumentAlreadyExistsException();
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
