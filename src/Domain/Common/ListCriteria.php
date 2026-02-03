<?php

declare(strict_types=1);

namespace App\Domain\Common;

/**
 * Critérios padrão de listagem (page, perPage, search, sort, filter).
 * Value object imutável reutilizável em todo o projeto.
 */
final class ListCriteria
{
    /** @param array<string, string> $sort coluna => 'asc'|'desc' */
    /** @param array<string, string|int|float|bool> $filter coluna => valor (WHERE equal) */
    public function __construct(
        private int $page,
        private int $perPage,
        private string $search,
        private array $sort,
        private array $filter,
    ) {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getSearch(): string
    {
        return $this->search;
    }

    /** @return array<string, string> */
    public function getSort(): array
    {
        return $this->sort;
    }

    /** @return array<string, string|int|float|bool> */
    public function getFilter(): array
    {
        return $this->filter;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasSearch(): bool
    {
        return $this->search !== '';
    }

    public function hasFilter(): bool
    {
        return $this->filter !== [];
    }
}
