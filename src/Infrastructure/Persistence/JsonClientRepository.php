<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Models\Client;
use App\Domain\Repositories\ClientRepositoryInterface;

final class JsonClientRepository implements ClientRepositoryInterface
{
    public function __construct(private string $storagePath)
    {
    }

    private function getFilePath(): string
    {
        return $this->storagePath . '/clients.json';
    }

    /** @return array<int, array{id: int, name: string, phone: string, type: string, document: string, created_at: string|null, updated_at: string|null}> */
    private function loadData(): array
    {
        $path = $this->getFilePath();
        if (!is_file($path)) {
            return [];
        }
        $json = file_get_contents($path);
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $data = [];
        foreach ($decoded as $row) {
            if (isset($row['id'])) {
                $data[(int) $row['id']] = $row;
            }
        }
        return $data;
    }

    private function saveData(array $data): void
    {
        $dir = dirname($this->getFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->getFilePath(), json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function findById(int $id): ?Client
    {
        $data = $this->loadData();
        $row = $data[$id] ?? null;
        return $row ? $this->rowToClient($row) : null;
    }

    /** @return array{items: Client[], total: int} */
    public function findAll(int $page = 1, int $perPage = 10): array
    {
        $data = $this->loadData();
        $total = count($data);
        $items = array_slice(array_values($data), ($page - 1) * $perPage, $perPage, true);
        $clients = array_map(fn (array $row) => $this->rowToClient($row), $items);
        return ['items' => $clients, 'total' => $total];
    }

    public function save(Client $client): Client
    {
        $data = $this->loadData();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($client->getId() === null) {
            $id = $data ? (int) max(array_keys($data)) + 1 : 1;
            $row = [
                'id' => $id,
                'name' => $client->getName(),
                'phone' => $client->getPhone(),
                'type' => $client->getType(),
                'document' => $client->getDocument(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $data[$id] = $row;
            $this->saveData($data);
            return $this->rowToClient($row);
        }

        $id = $client->getId();
        $existing = $data[$id] ?? null;
        $data[$id] = [
            'id' => $id,
            'name' => $client->getName(),
            'phone' => $client->getPhone(),
            'type' => $client->getType(),
            'document' => $client->getDocument(),
            'created_at' => $existing['created_at'] ?? $now,
            'updated_at' => $now,
        ];
        $this->saveData($data);
        return $this->rowToClient($data[$id]);
    }

    public function delete(int $id): bool
    {
        $data = $this->loadData();
        if (!isset($data[$id])) {
            return false;
        }
        unset($data[$id]);
        $this->saveData($data);
        return true;
    }

    /** @param array{id: int, name: string, phone: string, type: string, document: string, created_at: string|null, updated_at: string|null} $row */
    private function rowToClient(array $row): Client
    {
        return new Client(
            (int) $row['id'],
            $row['name'],
            $row['phone'],
            $row['type'],
            $row['document'],
            isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null,
            isset($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : null,
        );
    }
}
