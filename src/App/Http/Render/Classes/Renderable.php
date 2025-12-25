<?php

namespace App\Http\Prolancee\Classes;

use PROLANCEE\Support\Classes\Fetcher;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 *  PROLANCEE Renderable Class (6 Function Version)
 * ============================================================================
 * Provides reusable data-fetching logic for Blade partials using PROLANCEE’s
 * abstract Fetcher engine. Each method demonstrates a unique query pattern.
 * ============================================================================
 */
final class Renderable
{
    /*=========================================================================
     | 1️ METHOD: prepare_and_render_all_fields_html()
     |---------------------------------------------------------------------------
     | Fetches all fields (*) from a table without any filters or joins.
     *=========================================================================*/
    public static function prepare_and_render_all_fields_html(): array
    {
        $data = Fetcher::fetching([
            'TABLE' => 'users',
            'SELECT' => ['*'], // Fetch all columns
            'ORDER_BY' => [['id', 'asc']],
        ]);

        return ['data' => $data, 'FILE_PATH' => 'partials.users_all'];
    }

    /*=========================================================================
     | 2️ METHOD: prepare_and_render_joins_html()
     |---------------------------------------------------------------------------
     | Demonstrates complex JOIN-based fetching.
     *=========================================================================*/
    public static function prepare_and_render_joins_html(): array
    {
        $data = Fetcher::fetching([
            'TABLE' => 'users',
            'SELECT' => ['users.name as user_name', 'users.id as user_id'],
            'INNER_JOIN' => [['posts', 'users.id', '=', 'posts.user_id']],
            'LEFT_JOIN'  => [['profiles', 'users.id', '=', 'profiles.user_id']],
            'RIGHT_JOIN' => [['settings', 'users.id', '=', 'settings.user_id']],
            'CROSS_JOIN' => [['countries', null, null, null]],
            'SUBQUERY_JOIN' => [[
                'recent_orders',
                DB::table('orders')->where('created_at', '>', now()->subDays(30)),
                'users.id', '=', 'recent_orders.user_id', 'sub',
            ]],
            'WHERE' => [['users.status', '=', 'active']],
            'ORDER_BY' => [['users.created_at', 'desc']],
            'LIMIT' => 10,
        ]);

        return ['data' => $data, 'FILE_PATH' => 'partials.users_with_joins'];
    }

    /*=========================================================================
     | 3️ METHOD: prepare_and_render_where_html()
     |---------------------------------------------------------------------------
     | Demonstrates simple WHERE and OR_WHERE filters.
     *=========================================================================*/
    public static function prepare_and_render_where_html(): array
    {
        $data = Fetcher::fetching([
            'TABLE' => 'users',
            'SELECT' => ['name as user_name', 'id as user_id'],
            'WHERE' => [
                ['status', '=', 'active'],
                ['email_verified_at', '!=', null],
            ],
            'OR_WHERE' => [
                ['is_verified'],
            ],
            'ORDER_BY' => [['created_at', 'desc']],
            'LIMIT' => 20,
        ]);

        return ['data' => $data, 'FILE_PATH' => 'partials.users_where'];
    }

    /*=========================================================================
     | 4️ METHOD: prepare_and_render_between_html()
     |---------------------------------------------------------------------------
     | Demonstrates BETWEEN and NOT BETWEEN date filtering.
     *=========================================================================*/
    public static function prepare_and_render_between_html(): array
    {
        $data = Fetcher::fetching([
            'TABLE' => 'users',
            'SELECT' => ['name', 'created_at'],
            'BETWEEN' => [['users.created_at', [now()->subMonth(), now()]]],
            'NOT_BETWEEN' => [['users.updated_at', [now()->subYear(), now()->subMonth()]]],
            'ORDER_BY' => [['users.created_at', 'desc']],
            'LIMIT' => 15,
        ]);

        return ['data' => $data, 'FILE_PATH' => 'partials.users_between'];
    }

    /*=========================================================================
     | 5️ METHOD: prepare_and_render_in_html()
     |---------------------------------------------------------------------------
     | Demonstrates IN and NOT IN filtering.
     *=========================================================================*/
    public static function prepare_and_render_in_html(): array
    {
        $data = Fetcher::fetching([
            'TABLE' => 'users',
            'SELECT' => ['id', 'name', 'role'],
            'IN' => [
                ['role', ['admin', 'editor']],
                ['id', [1, 2, 3]],
            ],
            'NOT_IN' => [
                ['status', ['banned', 'suspended']],
            ],
            'ORDER_BY' => [['name', 'asc']],
        ]);

        return ['data' => $data, 'FILE_PATH' => 'partials.users_in'];
    }

    /*=========================================================================
     | 6️ METHOD: prepare_and_render_like_html()
     |---------------------------------------------------------------------------
     | Demonstrates LIKE and NOT LIKE filtering.
     *=========================================================================*/
    public static function prepare_and_render_like_html(): array
    {
        $data = Fetcher::fetching([
            'TABLE' => 'users',
            'SELECT' => ['id', 'name', 'email'],
            'LIKE' => [
                ['name', '%john%'],
                ['email', '%@example.com'],
            ],
            'NOT_LIKE' => [
                ['bio', '%banned%'],
                ['comment', '%spam%'],
            ],
            'ORDER_BY' => [['created_at', 'desc']],
            'LIMIT' => 25,
        ]);

        return ['data' => $data, 'FILE_PATH' => 'partials.users_like'];
    }

    /*=========================================================================
     | 7️ METHOD: prepare_and_render_all_html()
     |---------------------------------------------------------------------------
     | Demonstrates a full fetch combining JOINs, filters, BETWEEN, IN, LIKE.
     *=========================================================================*/
    public static function prepare_and_render_all_html(): array
    {
        $data = Fetcher::fetching([
            'TABLE' => 'users',
            'SELECT' => ['*'],
            'INNER_JOIN' => [['posts', 'users.id', '=', 'posts.user_id']],
            'LEFT_JOIN'  => [['profiles', 'users.id', '=', 'profiles.user_id']],
            'RIGHT_JOIN' => [['settings', 'users.id', '=', 'settings.user_id']],
            'CROSS_JOIN' => [['countries', null, null, null]],
            'SUBQUERY_JOIN' => [[
                'recent_orders',
                DB::table('orders')->where('created_at', '>', now()->subDays(30)),
                'users.id', '=', 'recent_orders.user_id', 'sub',
            ]],
            'WHERE' => [['users.status', '=', 'active']],
            'OR_WHERE' => [['users.is_verified']],
            'WHERE_RAW' => [['YEAR(created_at) = ?', [2024]]],
            'BETWEEN' => [['users.created_at', [now()->subMonth(), now()]]],
            'NOT_BETWEEN' => [['users.updated_at', [now()->subYear(), now()->subMonth()]]],
            'IN' => [['role', ['admin', 'editor']]],
            'NOT_IN' => [['status', ['banned', 'suspended']]],
            'LIKE' => [['users.name', '%john%']],
            'NOT_LIKE' => [['users.bio', '%banned%']],
            'ORDER_BY' => [['users.created_at', 'desc']],
            'LIMIT' => 50,
        ]);

        return ['data' => $data, 'FILE_PATH' => 'partials.users_all_combined'];
    }
}
