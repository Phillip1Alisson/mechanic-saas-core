<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Models\Client;
use App\Domain\Repositories\ClientRepositoryInterface;
use PDO;

final class MySQLClientRepository implements ClientRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?Client
    {
        $stmt = $this->pdo->prepare('SELECT id, name, phone, type, document, created_at, updated_at FROM clients WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->rowToClient($row) : null;
    }

    /** @return array{items: Client[], total: int} */
    public function findAll(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->query('SELECT COUNT(*) FROM clients');
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT id, name, phone, type, document, created_at, updated_at FROM clients ORDER BY id ASC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = $this->rowToClient($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function save(Client $client): Client
    {
        if ($client->getId() === null) {
            $stmt = $this->pdo->prepare('INSERT INTO clients (name, phone, type, document) VALUES (?, ?, ?, ?)');
            $stmt->execute([$client->getName(), $client->getPhone(), $client->getType(), $client->getDocument()]);
            $id = (int) $this->pdo->lastInsertId();
            $stmt = $this->pdo->prepare('SELECT id, name, phone, type, document, created_at, updated_at FROM clients WHERE id = ?');
            $stmt->execute([$id]);
            return $this->rowToClient($stmt->fetch(PDO::FETCH_ASSOC));
        }

        $stmt = $this->pdo->prepare('UPDATE clients SET name = ?, phone = ?, type = ?, document = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$client->getName(), $client->getPhone(), $client->getType(), $client->getDocument(), $client->getId()]);
        $stmt = $this->pdo->prepare('SELECT id, name, phone, type, document, created_at, updated_at FROM clients WHERE id = ?');
        $stmt->execute([$client->getId()]);
        return $this->rowToClient($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM clients WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    private function rowToClient(array $row): Client
    {
        return new Client(
            (int) $row['id'],
            $row['name'],
            $row['phone'],
            $row['type'],
            $row['document'],
            $row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null,
            $row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null,
        );
    }
}
