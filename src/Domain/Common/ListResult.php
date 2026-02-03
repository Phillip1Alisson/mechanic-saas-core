<?php

declare(strict_types=1);

namespace App\Domain\Common;

/**
 * Resultado padrão de listagem (items, total, page, perPage).
 * Value object reutilizável em todo o projeto.
 */
final class ListResult
{
    /** @param array<int, mixed> $items */
    public function __construct(
        private array $items,
        private int $total,
        private int $page,
        private int $perPage,
    ) {
    }

    /** @return array<int, mixed> */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}
