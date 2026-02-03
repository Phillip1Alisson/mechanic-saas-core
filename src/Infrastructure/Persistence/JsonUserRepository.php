<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Models\User;
use App\Domain\Repositories\UserRepositoryInterface;

final class JsonUserRepository implements UserRepositoryInterface
{
    public function __construct(private string $storagePath)
    {
    }

    private function getFilePath(): string
    {
        return $this->storagePath . '/users.json';
    }

    /** @return array<int, array{id: int, email: string, password_hash: string, created_at: string|null}> */
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

    public function findByEmail(string $email): ?User
    {
        $data = $this->loadData();
        foreach ($data as $row) {
            if (isset($row['email']) && strcasecmp($row['email'], $email) === 0) {
                return $this->rowToUser($row);
            }
        }
        return null;
    }

    /** @param array{id: int, email: string, password_hash: string, created_at: string|null} $row */
    private function rowToUser(array $row): User
    {
        return new User(
            (int) $row['id'],
            $row['email'],
            $row['password_hash'],
            !empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null,
        );
    }
}
