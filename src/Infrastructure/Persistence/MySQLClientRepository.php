<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Common\ListCriteria;
use App\Domain\Common\ListResult;
use App\Domain\Models\Client;
use App\Domain\Repositories\ClientRepositoryInterface;
use PDO;

final class MySQLClientRepository implements ClientRepositoryInterface
{
    private const SEARCH_COLUMNS = ['name', 'document', 'phone'];

    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?Client
    {
        $stmt = $this->pdo->prepare('SELECT id, name, phone, type, document, created_at, updated_at, deleted_at FROM clients WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->rowToClient($row) : null;
    }

    public function findAll(ListCriteria $criteria): ListResult
    {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($criteria->hasSearch()) {
            $conditions = [];
            foreach (self::SEARCH_COLUMNS as $col) {
                $conditions[] = '`' . $col . '` LIKE ?';
                $params[] = '%' . $criteria->getSearch() . '%';
            }
            $where[] = '(' . implode(' OR ', $conditions) . ')';
        }

        if ($criteria->hasFilter()) {
            foreach ($criteria->getFilter() as $column => $value) {
                $where[] = '`' . $column . '` = ?';
                $params[] = $value;
            }
        }

        $whereSql = implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM clients WHERE ' . $whereSql;
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $orderParts = [];
        foreach ($criteria->getSort() as $col => $dir) {
            $orderParts[] = '`' . $col . '` ' . ($dir === 'desc' ? 'DESC' : 'ASC');
        }
        $orderSql = $orderParts === [] ? 'id ASC' : implode(', ', $orderParts);

        $params[] = $criteria->getPerPage();
        $params[] = $criteria->getOffset();
        $stmt = $this->pdo->prepare(
            'SELECT id, name, phone, type, document, created_at, updated_at, deleted_at FROM clients WHERE ' . $whereSql . ' ORDER BY ' . $orderSql . ' LIMIT ? OFFSET ?'
        );
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = $this->rowToClient($row);
        }

        return new ListResult($items, $total, $criteria->getPage(), $criteria->getPerPage());
    }

    public function save(Client $client): Client
    {
        if ($client->getId() === null) {
            $stmt = $this->pdo->prepare('INSERT INTO clients (name, phone, type, document) VALUES (?, ?, ?, ?)');
            $stmt->execute([$client->getName(), $client->getPhone(), $client->getType(), $client->getDocument()]);
            $id = (int) $this->pdo->lastInsertId();
            $stmt = $this->pdo->prepare('SELECT id, name, phone, type, document, created_at, updated_at, deleted_at FROM clients WHERE id = ?');
            $stmt->execute([$id]);
            return $this->rowToClient($stmt->fetch(PDO::FETCH_ASSOC));
        }

        $stmt = $this->pdo->prepare('UPDATE clients SET name = ?, phone = ?, type = ?, document = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$client->getName(), $client->getPhone(), $client->getType(), $client->getDocument(), $client->getId()]);
        $stmt = $this->pdo->prepare('SELECT id, name, phone, type, document, created_at, updated_at, deleted_at FROM clients WHERE id = ?');
        $stmt->execute([$client->getId()]);
        return $this->rowToClient($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE clients SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
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
            !empty($row['deleted_at']) ? new \DateTimeImmutable($row['deleted_at']) : null,
        );
    }
}
