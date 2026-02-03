<?php

declare(strict_types=1);

namespace App\Application\Request;

use Psr\Http\Message\ServerRequestInterface;

final class LoginRequestValidator
{
    /** @return array{valid: bool, email?: string, password?: string, errors?: string[]} */
    public function validate(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return ['valid' => false, 'errors' => ['Body inválido ou vazio.']];
        }

        $email = isset($body['email']) ? trim((string) $body['email']) : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';

        $errors = [];
        if ($email === '') {
            $errors[] = 'E-mail é obrigatório.';
        }
        if ($password === '') {
            $errors[] = 'Senha é obrigatória.';
        }

        if ($errors !== []) {
            return ['valid' => false, 'errors' => $errors];
        }

        return ['valid' => true, 'email' => $email, 'password' => $password];
    }
}
