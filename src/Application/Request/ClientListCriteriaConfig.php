<?php

declare(strict_types=1);

namespace App\Application\Request;

/**
 * Configuração de listagem específica para o recurso Client.
 * Search: nome, documento, telefone. Sort: colunas permitidas. Filter: coluna = valor.
 */
final class ClientListCriteriaConfig extends ListCriteriaConfig
{
    private const SEARCHABLE = ['name', 'document', 'phone'];
    private const SORTABLE = ['id', 'name', 'phone', 'type', 'document', 'created_at', 'updated_at'];
    private const FILTERABLE = ['type'];

    public function __construct()
    {
        parent::__construct(
            self::SEARCHABLE,
            self::SORTABLE,
            self::FILTERABLE,
            maxPerPage: 200,
            defaultPerPage: 10,
            defaultSort: ['id' => 'asc'],
        );
    }
}
