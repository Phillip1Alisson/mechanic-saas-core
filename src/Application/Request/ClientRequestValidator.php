<?php

declare(strict_types=1);

namespace App\Application\Request;

use Psr\Http\Message\ServerRequestInterface;

final class ClientRequestValidator
{
    private const TYPE_PF = 'PF';
    private const TYPE_PJ = 'PJ';
    private const PHONE_DIGITS_MIN = 10;
    private const PHONE_DIGITS_MAX = 11;
    private const CPF_DIGITS = 11;
    private const CNPJ_DIGITS = 14;

    /** @return array{valid: bool, data?: array{name: string, phone: string, type: string, document: string}, errors?: string[]} */
    public function validateCreate(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return ['valid' => false, 'errors' => ['Body inválido ou vazio.']];
        }
        return $this->validate($body);
    }

    /** @return array{valid: bool, data?: array{name: string, phone: string, type: string, document: string}, errors?: string[]} */
    public function validateUpdate(ServerRequestInterface $request): array
    {
        return $this->validateCreate($request);
    }

    /** @param array<string, mixed> $data */
    private function validate(array $data): array
    {
        $errors = [];

        $name = $this->validateName($data, $errors);
        $phone = $this->validatePhone($data, $errors);
        $type = $this->validateType($data, $errors);
        $document = $this->validateDocument($data, $type, $errors);

        if ($errors !== []) {
            return ['valid' => false, 'errors' => $errors];
        }

        return [
            'valid' => true,
            'data' => [
                'name' => $name,
                'phone' => $phone,
                'type' => $type,
                'document' => $document,
            ],
        ];
    }

    /** @param array<string, mixed> $data @param list<string> $errors */
    private function validateName(array $data, array &$errors): string
    {
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        if ($name === '') {
            $errors[] = 'Nome é obrigatório.';
        }
        return $name;
    }

    /** @param array<string, mixed> $data @param list<string> $errors */
    private function validatePhone(array $data, array &$errors): string
    {
        $raw = isset($data['phone']) ? trim((string) $data['phone']) : '';
        $phone = preg_replace('/\D/', '', $raw);

        if ($phone === '') {
            $errors[] = 'Telefone é obrigatório.';
        } elseif (strlen($phone) < self::PHONE_DIGITS_MIN || strlen($phone) > self::PHONE_DIGITS_MAX) {
            $errors[] = 'Telefone deve conter 10 ou 11 dígitos.';
        }

        return $phone;
    }

    /** @param array<string, mixed> $data @param list<string> $errors */
    private function validateType(array $data, array &$errors): string
    {
        $type = isset($data['type']) ? strtoupper(trim((string) $data['type'])) : '';
        if ($type !== self::TYPE_PF && $type !== self::TYPE_PJ) {
            $errors[] = 'Tipo deve ser PF ou PJ.';
        }
        return $type;
    }

    /** @param array<string, mixed> $data @param string $type @param list<string> $errors */
    private function validateDocument(array $data, string $type, array &$errors): string
    {
        $document = isset($data['document']) ? preg_replace('/\D/', '', (string) $data['document']) : '';

        if ($document === '') {
            $errors[] = 'Documento (CPF/CNPJ) é obrigatório.';
        } elseif ($type === self::TYPE_PF && strlen($document) !== self::CPF_DIGITS) {
            $errors[] = 'CPF deve conter 11 dígitos.';
        } elseif ($type === self::TYPE_PJ && strlen($document) !== self::CNPJ_DIGITS) {
            $errors[] = 'CNPJ deve conter 14 dígitos.';
        }

        return $document;
    }
}
