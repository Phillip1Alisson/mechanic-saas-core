<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Models\User;
use App\Domain\Repositories\UserRepositoryInterface;

final class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private string $authSecret,
    ) {
    }

    public function login(string $email, string $password): ?string
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null || !$user->verifyPassword($password)) {
            return null;
        }
        return $this->generateToken($user);
    }

    /** Retorna o user id se o token for válido, null caso contrário */
    public function validateToken(string $token): ?int
    {
        $payload = base64_decode($token, true);
        if ($payload === false) {
            return null;
        }
        $data = json_decode($payload, true);
        if (!is_array($data) || !isset($data['user_id'], $data['exp'], $data['signature'])) {
            return null;
        }
        if ($data['exp'] < time()) {
            return null;
        }
        $expected = hash_hmac('sha256', $data['user_id'] . '|' . $data['exp'], $this->authSecret);
        if (!hash_equals($expected, $data['signature'])) {
            return null;
        }
        return (int) $data['user_id'];
    }

    private function generateToken(User $user): string
    {
        $exp = time() + (24 * 3600); // 24h
        $signature = hash_hmac('sha256', $user->getId() . '|' . $exp, $this->authSecret);
        $payload = [
            'user_id' => $user->getId(),
            'exp' => $exp,
            'signature' => $signature,
        ];
        return base64_encode(json_encode($payload));
    }
}
