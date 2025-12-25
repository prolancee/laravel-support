<?php

namespace PROLANCEE\Support\Classes\IO;

use PROLANCEE\Support\App\Http\Controllers\FetcherController;

final class Fetcher
{
    /**
     * Dynamically build and execute a query using FetcherController + FetcherService.
     *
     * This is the master Fetcher interface method which:
     * - Combines multiple query clauses (JOINs, WHEREs, BETWEENs, INs, RAW, etc.)
     * - Delegates execution to FetcherService via FetcherController
     * - Returns the resulting dataset along with the Blade partial view path
     *
     *
     * Blade Partial:
     * Injects the resolved Blade path via `'PROLANCEE_PARTIAL_FILE_PATH'` key in the result.
     *
     * @param  array  $query     Full query definition.
     * @param  string $filePath  Blade partial path to inject for render context.
     * @return array             Result set + render context.
     */
    public static function fetching(array $query): array
    {
        $query = array_merge([
            'TABLE'         => '',
            'SELECT'        => ['*'],
            'INNER_JOIN'    => [],
            'LEFT_JOIN'     => [],
            'RIGHT_JOIN'    => [],
            'CROSS_JOIN'    => [],
            'SUBQUERY_JOIN' => [],
            'WHERE'         => [],
            'OR_WHERE'      => [],
            'WHERE_RAW'     => [],
            'BETWEEN'       => [],
            'NOT_BETWEEN'   => [],
            'IN'            => [],
            'NOT_IN'        => [],
            'LIKE'          => [],
            'NOT_LIKE'      => [],
            'ORDER_BY'      => [],
            'OFFSET'        => 0,
            'LIMIT'         => '*',
        ], $query);

        return FetcherController::fetching(function () use ($query) {
            return [
                'table'  => $query['TABLE'],
                'select' => $query['SELECT'] ?? ['*'],
                'group' => [
                    'join' => [
                        ...array_map(fn($join) => [...$join, 'type' => 'inner'], $query['INNER_JOIN']),
                        ...array_map(fn($join) => [...$join, 'type' => 'left'], $query['LEFT_JOIN']),
                        ...array_map(fn($join) => [...$join, 'type' => 'right'], $query['RIGHT_JOIN']),
                        ...array_map(fn($join) => [...$join, 'type' => 'cross'], $query['CROSS_JOIN']),
                        ...$query['SUBQUERY_JOIN'],
                    ],
                    'where'        => $query['WHERE'],
                    'or_where'     => $query['OR_WHERE'],
                    'where_raw'    => $query['WHERE_RAW'],
                    'between'      => $query['BETWEEN'],
                    'not_between'  => $query['NOT_BETWEEN'],
                    'in'           => $query['IN'],
                    'not_in'       => $query['NOT_IN'],
                    'like'         => $query['LIKE'],
                    'not_like'     => $query['NOT_LIKE'],
                    'order_by'     => $query['ORDER_BY'],
                    'offset'       => $query['OFFSET'],
                    'limit'        => $query['LIMIT']
                ],
            ];
        });
    }
} 