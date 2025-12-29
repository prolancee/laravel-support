<?php

namespace PROLANCEE\Support\App\Repositories\Eloquent;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException as LaravelQueryException;
use Illuminate\Support\Facades\{DB, Hash, Session};
use Illuminate\Support\Str;
use PROLANCEE\Support\App\Models\EloquentModel;
use PROLANCEE\Support\App\Repositories\BaseRepositoryInterface;
use PROLANCEE\Support\App\Repositories\Eloquent\BaseHelperRepositry;
use PROLANCEE\Support\Classes\Routes\RouterTracker;
use PROLANCEE\Support\Exceptions\QueryException;

class BaseRepository extends BaseHelperRepositry implements BaseRepositoryInterface
{
    protected $model;
    protected $fromUri;

    public function __construct(EloquentModel $model)
    {
        $this->model   = $model;
        $this->fromUri = RouterTracker::getFromUri();
    }

    /*
    |--------------------------------------------------------------------------
    | register Methods
    |--------------------------------------------------------------------------
    */
    public function register(
        string $table,
        array $data,
        string $notifier,
        string $operation
    ): array|bool {
        DB::beginTransaction();

        try {
            if (
                $this->isParamValue($table) ||
                $this->isParamValue($data)
            ) {
                DB::rollBack();
                return false;
            }

            $this->model->setTable($table);
            $this->model->fillable = array_keys($data);

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $this->applyCreatedAt($table, $data);

            $id = $this->model->insertGetId($data);
            if (! $id) {
                DB::rollBack();
                return false;
            }

            $user = $this->model->newQuery()->whereKey($id)->first();
            if (! $user) {
                DB::rollBack();
                return false;
            }
            $module = $this->fromUri['module'] ?? null;

            $token            = null;
            $tokenInfo        = null;
            $ajaxSessionData  = null;

            if ($module === 'sanctum' && method_exists($user, 'createToken')) {
                $token = $user->createToken('api_token')->plainTextToken;
            }
            if ($module === 'admotum') {
                $tokenInfo = 'Generate token at: admotum/generate/access-token';
            }

            if ($module === 'ajax') {
                Session::regenerate(); 

                $ajaxSessionData = [
                    'user_id'    => $user->getKey(),
                    'token_info' => 'Session based authentication (AJAX)',
                ];

                Session::put($ajaxSessionData);
            }

            DB::commit();

            $results = [
                'token'      => $token,
                'token_info' => $tokenInfo,
                'row'        => $user->toArray(),
                'session'    => $ajaxSessionData,
            ];

            $notified = $this->buildAndNotifier(
                $operation,
                $results,
                $notifier
            );

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | login Methods
    |--------------------------------------------------------------------------
    */
    public function login(
        string $table,
        string $column,
        int|string $unique,
        string $password,
        string $notifier,
        string $operation
    ): array|bool {

        try {
            if (
                $this->isParamValue($table) ||
                $this->isParamValue($column) ||
                $this->isParamValue($unique) ||
                $this->isParamValue($password)
            ) {
                return false;
            }

            $this->model->setTable($table);

            $user = $this->model->newQuery()->where($column, $unique)->first();
            if (! $user) {
                return [
                    'auth' => 'user-not-found',
                ];
            }

            if (! Hash::check($password, $user->password)) {
                return [
                    'auth' => 'invalid-credentials',
                ];
            }

            $module = $this->fromUri['module'] ?? null;

            $token           = null;
            $tokenInfo       = null;
            $ajaxSessionData = null;

            if ($module === 'sanctum' && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
                $token = $user->createToken('api_token')->plainTextToken;
            }

            if ($module === 'admotum') {
                $tokenInfo = 'Generate token at: admotum/generate/access-token';
            }

            if ($module === 'ajax') {
                Session::regenerate();

                $ajaxSessionData = [
                    'user_id'    => $user->getKey(),
                    'token_info' => 'Session based authentication (AJAX)',
                ];

                Session::put($ajaxSessionData);
            }

            $results = [
                'token'      => $token,
                'token_info' => $tokenInfo,
                'row'        => $user->toArray(),
                'session'    => $ajaxSessionData,
            ];

            $notified = $this->buildAndNotifier(
                $operation,
                $results,
                $notifier
            );

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | passwordResetToken Methods
    |--------------------------------------------------------------------------
    */
    public function passwordResetToken(
        string $email, 
        string $notifier,
        string $operation
    ): array | bool {
        DB::beginTransaction();

        try {
            if ($this->isParamValue($email)) {
                DB::rollBack();
                return false;
            }

            $this->ensurePasswordResetTable();
            $this->deletePasswordResetToken($email);

            $plainToken  = Str::random(64);
            $hashedToken = Hash::make($plainToken);

            DB::table('password_reset_tokens')->insert([
                'email'      => $email,
                'token'      => $hashedToken,
                'created_at' => now(),
            ]);

            DB::commit();

            $results = [
                'email'      => $email,
                'token'      => $plainToken,
                'expired_in' => '1 hour',
            ];
            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | changePassword Methods
    |--------------------------------------------------------------------------
    */
    public function changePassword(
        string $table,
        string $column,
        int | string $unique,
        string $password,
        string $token,
        string $notifier,
        string $operation
    ): array | bool {
        DB::beginTransaction();

        try {
            if (
                $this->isParamValue($table) ||
                $this->isParamValue($column) ||
                $this->isParamValue($unique) ||
                $this->isParamValue($password) ||
                $this->isParamValue($token)
            ) {
                DB::rollBack();
                return false;
            }

            $this->ensurePasswordResetTable();
            $resetRecord = $this->getPasswordResetRecord($unique);

            if (! $resetRecord || ! Hash::check($token, $resetRecord->token)) {
                DB::rollBack();
                return ['auth' => 'invalid-or-expired-token'];
            }

            if (Carbon::parse($resetRecord->created_at)->addHour()->isPast()) {
                $this->deletePasswordResetToken($unique);
                DB::rollBack();
                return ['auth' => 'token-expired'];
            }

            $data = [
                'password' => Hash::make($password),
            ];

            $this->model->setTable($table);
            $this->model->fillable = array_keys($data);
            $this->applyUpdatedAt($table, $data);

            $user = $this->model->newQuery()
                ->where($column, $unique)
                ->first();

            if (! $user || ! $user->update($data)) {
                DB::rollBack();
                return false;
            }
            $this->deletePasswordResetToken($unique);

            DB::commit();

            $results = $user->toArray();

            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | logout Methods
    |--------------------------------------------------------------------------
    */
    public function logout(
        string $table,
        string $column,
        int | string $unique,
        string $notifier,
        string $operation
    ): array | bool {
        $fromUri = RouterTracker::getFromUri();
        $module  = $fromUri['module'] ?? null;

        try {
            if (
                $this->isParamValue($table) ||
                $this->isParamValue($column) ||
                $this->isParamValue($unique)
            ) {
                return false;
            }

            $this->model->setTable($table);

            $user = $this->model->newQuery()
                ->where($column, $unique)
                ->first();

            if (! $user) {
                return false;
            }
            if ($module === 'sanctum' && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            $results = [
                'unique' => $unique,
                'logout' => true,
            ];

            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified
            ];

        } catch (LaravelQueryException $e) {
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Store Methods
    |--------------------------------------------------------------------------
    */
    public function storeSingle(
        string $table,
        array $data,
        string $notifier,
        string $operation
    ): array | bool {
        DB::beginTransaction();

        try {
            if ($this->isParamValue($table) ||
                $this->isParamValue($data)
            ) {
                DB::rollBack();
                return false;
            }
            $this->model->setTable($table);
            $this->model->fillable = array_keys($data);

            $this->applyCreatedAt($table, $data);
            $id = $this->model->insertGetId($data);

            if (! $id) {
                DB::rollBack();
                return false;
            }
            $record = $this->model->newQuery()
                ->whereKey($id)
                ->first();

            DB::commit();

            $results  = ['row' => $record->toArray()];
            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    public function storeBulk(
        array $tables,
        array $data,
        string $notifier,
        string $operation
    ): array | bool {
        DB::beginTransaction();

        try {
            if ($this->isParamValue($table)) {
                DB::rollBack();
                return false;
            }
            $results = [];

            foreach ($tables as $tbl) {

                if ($this->isParamValue($data[$tbl])) {
                    DB::rollBack();
                    return false;
                }
                $this->model->setTable($tbl);

                $records = $data[$tbl];
                if (! isset($records[0])) {
                    $records = [$records];
                }
                $this->model->fillable = array_keys($records[0]);

                foreach ($records as $key => $row) {

                    $this->applyCreatedAt($tbl, $row);
                    $id = $this->model->insertGetId($row);

                    if (! $id) {
                        DB::rollBack();
                        return false;
                    }
                    $record = $this->model->newQuery()
                        ->whereKey($id)
                        ->first();

                    if (! $record) {
                        DB::rollBack();
                        return false;
                    }

                    $results["row{$key}"][] = $record->toArray();
                }
            }

            DB::commit();

            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Methods
    |--------------------------------------------------------------------------
    */
    public function fetchSingle(
        string $table,
        string $column,
        int | string $unique,
        string $notifier,
        string $operation
    ): array | bool {
        try {
            if ($this->isParamValue($table) ||
                $this->isParamValue($column) ||
                $this->isParamValue($unique)
            ) {
                return false;
            }
            $this->model->setTable($table);

            $results = $this->model->newQuery()->where($column, $unique)->first()?->toArray();

            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified
            ];

        } catch (LaravelQueryException $e) {
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    public function fetchBuilder(
        string $table,
        array $select = ['*'],
        array $joins = [],
        array $where = [],
        array $orWhere = [],
        array $whereRaw = [],
        array $between = [],
        array $notBetween = [],
        array $in = [],
        array $notIn = [],
        array $like = [],
        array $notLike = [],
        array $orderBy = [],
        int $offset = 0,
        int|string $limit = '*',
        string $notifier,
        string $operation
    ): array|bool {
        try {
            if ($this->isParamValue($table)) {
                return false;
            }
            $this->model->setTable($table);

            $query = $this->model->newQuery()->select($select);
            $results = [];
            $limitReached = false;

            // --- Helper closures ---
            $applyConditions = function ($conditions, $method, $nullMethod) use ($query) {
                foreach ($conditions as $cond) {
                    match (count($cond)) {
                        1 => $query->{$nullMethod}($cond[0]),
                        2 => $query->{$method}($cond[0], '=', $cond[1]),
                        default => $query->{$method}($cond[0], $cond[1], $cond[2] ?? null),
                    };
                }
            };

            // --- Joins ---
            foreach ($joins as $join) {
                if (!is_array($join) || count($join) < 4) continue;

                $type = strtolower($join[5] ?? $join[4] ?? 'inner');

                if ($type === 'sub' && count($join) >= 5) {
                    [$alias, $subquery, $left, $operator, $right] = $join;
                    $query->joinSub($subquery, $alias, fn($j) => $j->on($left, $operator, $right));
                } else {
                    [$tableJoin, $left, $operator, $right] = array_slice($join, 0, 4);
                    $method = match ($type) {
                        'left'  => 'leftJoin',
                        'right' => 'rightJoin',
                        'cross' => 'crossJoin',
                        default => 'join',
                    };
                    $query->{$method}($tableJoin, $left, $operator, $right);
                }
            }

            // --- Where / OrWhere ---
            $applyConditions($where, 'where', 'whereNotNull');
            $applyConditions($orWhere, 'orWhere', 'orWhereNotNull');

            // --- Raw ---
            foreach ($whereRaw as $raw) {
                is_string($raw)
                    ? $query->whereRaw($raw)
                    : $query->whereRaw($raw[0], $raw[1] ?? []);
            }

            // --- Between / NotBetween ---
            foreach ($between as $cond)
                if (isset($cond[0], $cond[1]) && count($cond[1]) === 2)
                    $query->whereBetween($cond[0], $cond[1]);

            foreach ($notBetween as $cond)
                if (isset($cond[0], $cond[1]) && count($cond[1]) === 2)
                    $query->whereNotBetween($cond[0], $cond[1]);

            // --- In / NotIn ---
            foreach ($in as $cond)
                if (count($cond) === 2) $query->whereIn($cond[0], $cond[1]);

            foreach ($notIn as $cond)
                if (count($cond) === 2) $query->whereNotIn($cond[0], $cond[1]);

            // --- Like / NotLike ---
            foreach ($like as $cond)
                if (count($cond) >= 2) $query->where($cond[0], 'like', $cond[1]);

            foreach ($notLike as $cond)
                if (count($cond) >= 2) $query->where($cond[0], 'not like', $cond[1]);

            // --- OrderBy ---
            foreach ($orderBy as $order)
                $query->orderBy($order[0], $order[1] ?? 'asc');

            // --- Pagination ---
            $query->chunk(1000, function ($chunk) use (&$results, $offset, $limit, &$limitReached) {
                $results = array_merge($results, $chunk->toArray());
                if ($limit !== '*' && count($results) >= $limit && !$limitReached) {
                    $results = array_slice($results, $offset, $limit);
                    $limitReached = true;
                    return false;
                }
            });

            if (!$limitReached && $limit !== '*') {
                $results = array_slice($results, $offset, $limit);
            }

            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Methods
    |--------------------------------------------------------------------------
    */
    public function updateSingle(
        string $table,
        string $column,
        int | string $unique,
        array $data,
        string $notifier,
        string $operation
    ): array | bool {
        DB::beginTransaction();

        try {
            if ($this->isParamValue($table) ||
                $this->isParamValue($column) ||
                $this->isParamValue($unique) ||
                $this->isParamValue($data)
            ) {
                DB::rollBack();
                return false;
            }
            $this->model->setTable($table);
            $this->model->fillable = array_keys($data);

            $this->applyUpdatedAt($table, $data);
            $record = $this->model->newQuery()->where($column, $unique)->first();

            if (! $record) {
                DB::rollBack();
                return false;
            }
            if (! $record->update($data)) {
                DB::rollBack();
                return false;
            }
            $record->refresh();

            DB::commit();

            $results  = ['row' => $record->toArray()];
            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    public function updateBulk(
        array $table,
        array $column,
        array $unique,
        array $data,
        string $notifier,
        string $operation
    ): array | bool {
        DB::beginTransaction();

        try {
            if ($this->isParamValue($table)) {
                DB::rollBack();
                return false;
            }

            $results = [];

            foreach ($table as $key => $tbl) {

                if ($this->isParamValue($data[$tbl]) ||
                    $this->isParamValue($unique[$tbl])
                ) {
                    DB::rollBack();
                    return false;
                }
                $this->model->setTable($tbl);

                $this->model->fillable = array_keys($data[$tbl]);
                $this->applyUpdatedAt($tbl, $data[$tbl]);

                $query = $this->model->newQuery();
                $this->applyConditions($query, $unique[$tbl]);

                $record = $query->first();

                if (! $record) {
                    DB::rollBack();
                    return false;
                }
                if (! $record->update($data[$tbl])) {
                    DB::rollBack();
                    return false;
                }
                $record->refresh();

                $results["row{$key}"][] = $record->toArray();
            }

            DB::commit();

            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified,
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Methods
    |--------------------------------------------------------------------------
    */
    public function deleteSingle(
        string $table,
        string $column,
        int | string $unique,
        string $notifier,
        string $operation
    ): array | bool {
        DB::beginTransaction();

        try {
            if ($this->isParamValue($table) ||
                $this->isParamValue($column) ||
                $this->isParamValue($unique)
            ) {
                DB::rollBack();
                return false;
            }
            $this->model->setTable($table);

            $deletedCount = $this->model->newQuery()
                ->where($column, $unique)
                ->delete();

            if ($deletedCount <= 0) {
                DB::rollBack();
                return false;
            }

            DB::commit();

            $results = [
                'unique'  => $unique,
                'deleted' => true,
            ];

            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }

    public function deleteBulk(
        array $table,
        array $column,
        array $unique,
        string $notifier,
        string $operation
    ): array | bool {
        DB::beginTransaction();

        try {
            if ($this->isParamValue($table)) {
                DB::rollBack();
                return false;
            }

            $results = [];

            foreach ($table as $tbl) {

                if ($this->isParamValue($unique[$tbl])) {
                    DB::rollBack();
                    return false;
                }
                $this->model->setTable($tbl);

                $query = $this->model->newQuery();
                $this->applyConditions($query, $unique[$tbl]);

                $record = $query->first();

                if (! $record) {
                    DB::rollBack();
                    return false;
                }
                $deletedCount = $query->delete();

                if ($deletedCount <= 0) {
                    DB::rollBack();
                    return false;
                }

                $results[$tbl][] = [
                    'unique'  => $unique[$tbl],
                    'deleted' => true,
                ];
            }

            DB::commit();

            $notified = $this->buildAndNotifier($operation, $results, $notifier);

            return [
                'data'     => $results,
                'notified' => $notified
            ];

        } catch (LaravelQueryException $e) {
            DB::rollBack();
            return [
                'query_exception' => QueryException::queryException($e),
            ];
        }
    }
}
