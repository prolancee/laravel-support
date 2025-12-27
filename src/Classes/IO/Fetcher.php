<?php

namespace PROLANCEE\Support\Classes\IO;

use PROLANCEE\Support\App\Http\BaseFetcher;
use PROLANCEE\Support\App\Services\BaseService;

final class Fetcher extends BaseFetcher
{
    public function __construct(BaseService $service)
    {
        parent::__construct($service);
    }

    /**
     * Dynamically build and execute a database query using BaseFetcher + BaseService.
     *
     * @param  array  $query  Full query definition.
     * @return array         Query result as array.
     */
    public static function fetch(array $query): array
    {
        $instance = app(self::class);

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
            'NOTIFIER'      => '',
            'OPERATION'     => 'fetch',
        ], $query);

        return $instance->execute(function () use ($query) {
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
                    'limit'        => $query['LIMIT'],
                    'notifier'     => $query['NOTIFIER'],
                    'operation'    => $query['OPERATION']
                ],
            ];
        });
    }
} 