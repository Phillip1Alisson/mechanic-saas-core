<?php

declare(strict_types=1);

namespace App\Application\Request;

use App\Domain\Common\ListCriteria;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Converte query params da requisição em ListCriteria usando a config do recurso.
 * Padrão do projeto: page, perPage, search, sort, filter.
 */
final class ListCriteriaParser
{
    public function __invoke(ServerRequestInterface $request, ListCriteriaConfig $config): ListCriteria
    {
        $params = $request->getQueryParams();

        $page = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : $config->getDefaultPerPage();
        $perPage = min(max(1, $perPage), $config->getMaxPerPage());

        $search = isset($params['search']) ? trim((string) $params['search']) : '';

        $sort = $this->parseSort(
            isset($params['sort']) ? trim((string) $params['sort']) : '',
            $config
        );
        if ($sort === []) {
            $sort = $config->getDefaultSort();
        }

        $filter = $this->parseFilter($params, $config);

        return new ListCriteria($page, $perPage, $search, $sort, $filter);
    }

    /** @return array<string, string> */
    private function parseSort(string $sortParam, ListCriteriaConfig $config): array
    {
        if ($sortParam === '') {
            return [];
        }
        $result = [];
        foreach (explode(',', $sortParam) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $dir = 'asc';
            if (str_contains($part, ':')) {
                [$col, $dir] = array_map('trim', explode(':', $part, 2));
                $dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';
            } else {
                $col = $part;
            }
            if ($config->isSortable($col)) {
                $result[$col] = $dir;
            }
        }
        return $result;
    }

    /** @param array<string, mixed> $params */
    /** @return array<string, string|int|float|bool> */
    private function parseFilter(array $params, ListCriteriaConfig $config): array
    {
        $filter = [];
        $filterParams = $params['filter'] ?? [];
        if (is_array($filterParams)) {
            foreach ($filterParams as $column => $value) {
                if (!is_string($column) || !$config->isFilterable($column)) {
                    continue;
                }
                if (is_scalar($value)) {
                    $filter[$column] = $value;
                }
            }
        }
        $reserved = ['page', 'perPage', 'search', 'sort', 'filter'];
        foreach ($config->getFilterableColumns() as $column) {
            if (in_array($column, $reserved, true)) {
                continue;
            }
            if (isset($params[$column]) && is_scalar($params[$column]) && !isset($filter[$column])) {
                $filter[$column] = $params[$column];
            }
        }
        return $filter;
    }
}
