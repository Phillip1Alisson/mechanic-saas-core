<?php

declare(strict_types=1);

namespace App\Domain\Models;

final class User
{
    public function __construct(
        private int $id,
        private string $email,
        private string $passwordHash,
        private ?\DateTimeImmutable $createdAt = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}
