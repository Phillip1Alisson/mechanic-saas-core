<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Common\ListCriteria;
use App\Domain\Common\ListResult;
use App\Domain\Models\Client;
use App\Domain\Repositories\ClientRepositoryInterface;

final class JsonClientRepository implements ClientRepositoryInterface
{
    private const SEARCH_COLUMNS = ['name', 'document', 'phone'];

    public function __construct(private string $storagePath)
    {
    }

    private function getFilePath(): string
    {
        return $this->storagePath . '/clients.json';
    }

    /** @return array<int, array{id: int, name: string, phone: string, type: string, document: string, created_at: string|null, updated_at: string|null, deleted_at?: string|null}> */
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
        if ($row === null || !empty($row['deleted_at'])) {
            return null;
        }
        return $this->rowToClient($row);
    }

    public function findAll(ListCriteria $criteria): ListResult
    {
        $data = $this->loadData();
        $active = array_filter($data, fn (array $row) => empty($row['deleted_at']));

        if ($criteria->hasSearch()) {
            $search = mb_strtolower($criteria->getSearch());
            $active = array_filter($active, function (array $row) use ($search) {
                foreach (self::SEARCH_COLUMNS as $col) {
                    if (isset($row[$col]) && mb_strpos(mb_strtolower((string) $row[$col]), $search) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        if ($criteria->hasFilter()) {
            foreach ($criteria->getFilter() as $column => $value) {
                $active = array_filter($active, fn (array $row) => isset($row[$column]) && (string) $row[$column] === (string) $value);
            }
        }

        $active = array_values($active);
        $total = count($active);

        $sort = $criteria->getSort();
        if ($sort !== []) {
            usort($active, function (array $a, array $b) use ($sort) {
                foreach ($sort as $col => $dir) {
                    $va = $a[$col] ?? '';
                    $vb = $b[$col] ?? '';
                    $cmp = is_numeric($va) && is_numeric($vb) ? $va <=> $vb : strcmp((string) $va, (string) $vb);
                    if ($cmp !== 0) {
                        return $dir === 'desc' ? -$cmp : $cmp;
                    }
                }
                return 0;
            });
        }

        $page = $criteria->getPage();
        $perPage = $criteria->getPerPage();
        $slice = array_slice($active, ($page - 1) * $perPage, $perPage);
        $items = array_map(fn (array $row) => $this->rowToClient($row), $slice);

        return new ListResult($items, $total, $page, $perPage);
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
                'deleted_at' => null,
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
            'deleted_at' => null,
        ];
        $this->saveData($data);
        return $this->rowToClient($data[$id]);
    }

    public function delete(int $id): bool
    {
        $data = $this->loadData();
        if (!isset($data[$id]) || !empty($data[$id]['deleted_at'])) {
            return false;
        }
        $data[$id]['deleted_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->saveData($data);
        return true;
    }

    /** @param array{id: int, name: string, phone: string, type: string, document: string, created_at: string|null, updated_at: string|null, deleted_at?: string|null} $row */
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
            !empty($row['deleted_at']) ? new \DateTimeImmutable($row['deleted_at']) : null,
        );
    }
}
