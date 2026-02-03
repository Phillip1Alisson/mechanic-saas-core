<?php

declare(strict_types=1);

namespace App\Application\Request;

/**
 * Configuração de critérios de listagem por recurso.
 * Define colunas searchable, sortable, filterable e limites (Clean: Single Responsibility).
 */
class ListCriteriaConfig
{
    /** @param string[] $searchableColumns */
    /** @param string[] $sortableColumns */
    /** @param string[] $filterableColumns */
    /** @param array<string, string> $defaultSort coluna => asc|desc */
    public function __construct(
        private array $searchableColumns,
        private array $sortableColumns,
        private array $filterableColumns,
        private int $maxPerPage = 200,
        private int $defaultPerPage = 10,
        private array $defaultSort = ['id' => 'asc'],
    ) {
    }

    /** @return string[] */
    public function getSearchableColumns(): array
    {
        return $this->searchableColumns;
    }

    /** @return string[] */
    public function getSortableColumns(): array
    {
        return $this->sortableColumns;
    }

    /** @return string[] */
    public function getFilterableColumns(): array
    {
        return $this->filterableColumns;
    }

    public function getMaxPerPage(): int
    {
        return $this->maxPerPage;
    }

    public function getDefaultPerPage(): int
    {
        return $this->defaultPerPage;
    }

    /** @return array<string, string> */
    public function getDefaultSort(): array
    {
        return $this->defaultSort;
    }

    public function isSortable(string $column): bool
    {
        return in_array($column, $this->sortableColumns, true);
    }

    public function isFilterable(string $column): bool
    {
        return in_array($column, $this->filterableColumns, true);
    }
}
