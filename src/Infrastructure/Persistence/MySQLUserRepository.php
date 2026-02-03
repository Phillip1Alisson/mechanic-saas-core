<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Models\User;
use App\Domain\Repositories\UserRepositoryInterface;
use PDO;

final class MySQLUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, created_at FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->rowToUser($row) : null;
    }

    /** @param array{id: int, email: string, password_hash: string, created_at: string|null} $row */
    private function rowToUser(array $row): User
    {
        return new User(
            (int) $row['id'],
            $row['email'],
            $row['password_hash'],
            $row['created_at'] ? new \DateTimeImmutable($row['created_at']) : null,
        );
    }
}
