<?php

namespace PROLANCEE\Support\App\Repositories\Eloquent;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException as LaravelQueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
    | Signup Method
    |--------------------------------------------------------------------------
    */
    public function signup(
        string $table,
        array $data,
        string $notifier,
        string $operation
    ): array | bool {
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
            $user = $this->model->newQuery()
                ->whereKey($id)
                ->first();

            if (! $user) {
                DB::rollBack();
                return false;
            }
            $module = $this->fromUri['module'] ?? null;

            $token = ($module === 'sanctum' && method_exists($user, 'createToken'))
                ? $user->createToken('api_token')->plainTextToken
                : null;

            $tokenInfo = ($module === 'admotum')
                ? 'Generate token at: admotum/generate/access-token'
                : null;

            DB::commit();

            $results = [
                'token'      => $token,
                'token_info' => $tokenInfo,
                'row'        => $user->toArray(),
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
    | signin Methods
    |--------------------------------------------------------------------------
    */
    public function signin(
        string $table,
        string $column,
        int | string $unique,
        string $password,
        string $notifier,
        string $operation
    ): array | bool {

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

            $user = $this->model->newQuery()
                ->where($column, $unique)
                ->first();

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

            if ($module === 'sanctum' && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            $token = $module === 'sanctum'
                ? $user->createToken('api_token')->plainTextToken
                : null;

            $tokenInfo = $module === 'admotum'
                ? 'Generate token at: admotum/generate/access-token'
                : null;

            $results = [
                'token'      => $token,
                'token_info' => $tokenInfo,
                'row'        => $user->toArray(),
            ];
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
    | register Methods
    |--------------------------------------------------------------------------
    */
    public function register(
        string $table,
        array $data,
        string $notifier,
        string $operation
    ): array | bool {
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
            $user = $this->model->newQuery()
                ->whereKey($id)
                ->first();

            if (! $user) {
                DB::rollBack();
                return false;
            }
            $module = $this->fromUri['module'] ?? null;

            $token = ($module === 'sanctum' && method_exists($user, 'createToken'))
                ? $user->createToken('api_token')->plainTextToken
                : null;

            $tokenInfo = ($module === 'admotum')
                ? 'Generate token at: admotum/generate/access-token'
                : null;

            DB::commit();

            $results = [
                'token'      => $token,
                'token_info' => $tokenInfo,
                'row'        => $user->toArray(),
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
    | login Methods
    |--------------------------------------------------------------------------
    */
    public function login(
        string $table,
        string $column,
        int | string $unique,
        string $password,
        string $notifier,
        string $operation
    ): array | bool {

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

            $user = $this->model->newQuery()
                ->where($column, $unique)
                ->first();

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

            if ($module === 'sanctum' && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            $token = $module === 'sanctum'
                ? $user->createToken('api_token')->plainTextToken
                : null;

            $tokenInfo = $module === 'admotum'
                ? 'Generate token at: admotum/generate/access-token'
                : null;

            $results = [
                'token'      => $token,
                'token_info' => $tokenInfo,
                'row'        => $user->toArray(),
            ];
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

    public function storeMultiple(
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

    public function fetchMultiple(
        string $table,
        array $orderBy,
        int $offset,
        int | string $limit,
        string $notifier,
        string $operation
    ): array | bool {
        try {
            if ($this->isParamValue($table)) {
                return false;
            }

            $this->model->setTable($table);
            $processedData = [];
            $query         = $this->model;

            if (! $this->isParamValue($orderBy)) {
                foreach ($orderBy as $order) {
                    if (is_array($order) && count($order) === 2) {
                        $query = $query->orderBy($order[0], $order[1]);
                    }
                }
            }

            if ($limit !== '*') {
                $query         = $query->skip($offset)->take((int) $limit);
                $processedData = $query->get()->toArray();
            } else {
                $query->chunk(1000, function ($chunk) use (&$processedData) {
                    $processedData = array_merge($processedData, $chunk->toArray());
                });
            }

            $notified = $this->buildAndNotifier($operation, $processedData, $notifier);

            return [
                'data'     => $processedData,
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

    public function updateMultiple(
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

    public function deleteMultiple(
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
