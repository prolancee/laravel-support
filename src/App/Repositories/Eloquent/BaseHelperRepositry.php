<?php

namespace PROLANCEE\Support\App\Repositories\Eloquent;

use Illuminate\Support\Facades\{DB, Schema, Cache};
use LogicException;
use PROLANCEE\Support\Classes\Routes\RouterTracker;
use Throwable;

class BaseHelperRepositry
{
    protected array $columnsCache = [];
    protected $fromUri;
    protected $notifierClass = 'App\\Notifier\\Prolancee';

    public function __construct()
    {
        $this->fromUri = RouterTracker::getFromUri();
    }

    /*
    |--------------------------------------------------------------------------
    | applyConditions Method
    |--------------------------------------------------------------------------
    */
    protected function applyConditions($query, array $conditions): void
    {
        foreach ($conditions as $column => $value) {
            if (stripos($column, '<AND>') !== false) {
                $cleanCol = str_ireplace(['<AND>', '<and>'], '', $column);
                is_array($value) ? $query->whereIn($cleanCol, $value) : $query->where($cleanCol, $value);
            } elseif (stripos($column, '<OR>') !== false) {
                $cleanCol = str_ireplace(['<OR>', '<or>'], '', $column);
                is_array($value) ? $query->orWhereIn($cleanCol, $value) : $query->orWhere($cleanCol, $value);
            } else {
                is_array($value) ? $query->whereIn($column, $value) : $query->where($column, $value);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | getTableColumns Method
    |--------------------------------------------------------------------------
    */
    protected function getTableColumns(string $table): array
    {
        return Cache::rememberForever("table_columns_{$table}", function () use ($table) {
            return Schema::getColumnListing($table);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | applyCreatedAt Method
    |--------------------------------------------------------------------------
    */
    protected function applyCreatedAt(string $table, array &$data): void
    {
        $columns = $this->getTableColumns($table);
        $now     = now();

        if (in_array('created_at', $columns) && ! isset($data['created_at'])) {
            $data['created_at'] = $now;
        }
        if (in_array('updated_at', $columns) && ! isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | applyUpdatedAt Method
    |--------------------------------------------------------------------------
    */
    protected function applyUpdatedAt(string $table, array &$data): void
    {
        $columns = $this->getTableColumns($table);
        $now     = now();

        if (in_array('updated_at', $columns) && ! isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | isParamValue Method
    |--------------------------------------------------------------------------
    */
    protected function isParamValue(mixed $value): bool
    {
        return in_array($value, [null, '', 0, '0', false, []], true);
    }

    /*
    |--------------------------------------------------------------------------
    | getPasswordResetRecord Method
    |--------------------------------------------------------------------------
    */
    protected function getPasswordResetRecord(string $email): ?object
    {
        return DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | deletePasswordResetToken Method
    |--------------------------------------------------------------------------
    */
    protected function deletePasswordResetToken(string $email): void
    {
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | ensurePasswordResetTable Method
    |--------------------------------------------------------------------------
    */
    protected function ensurePasswordResetTable(): void
    {
        $table = 'password_reset_tokens';

        if (! Schema::hasTable($table)) {
            Schema::create($table, function ($table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        Schema::table($table, function ($tableBlueprint) use ($table) {

            if (! Schema::hasColumn($table, 'email')) {
                $tableBlueprint->string('email')->index();
            }

            if (! Schema::hasColumn($table, 'token')) {
                $tableBlueprint->string('token');
            }

            if (! Schema::hasColumn($table, 'created_at')) {
                $tableBlueprint->timestamp('created_at')->nullable();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | buildAndNotifier Method
    |--------------------------------------------------------------------------
    */
    protected function buildAndNotifier(
        string $operation,
        array $payload,
        string $notifier
    ): array {
        $notified = [];

        if (strcasecmp($notifier, 'process') !== 0) {
            return $notified;
        }
        $notified = [
            'notifier_class'  => $this->notifierClass,
            'notifier_method' => 'handle(array $payload)',
            'hint'            => 'Notifier executed after successful operation.',
            'payload'         => [
                'operation'   => $operation,
                'requestPath' => $this->fromUri['requestPath'] ?? null,
                'row'         => $payload,
            ],
        ];

        if (! empty($this->notifierClass) && class_exists($this->notifierClass)) {
            try {
                $instance = app($this->notifierClass);

                if (! method_exists($instance, 'handle')) {
                    throw new LogicException(
                        "Notifier class {$this->notifierClass} must define handle(array \$payload)"
                    );
                }

                $instance->handle($notified['payload']);

            } catch (Throwable $e) {
                logger()->error('Notifier execution failed', [
                    'notifier_class' => $this->notifierClass,
                    'operation'      => $operation,
                    'error'          => $e->getMessage(),
                ]);

                $notified['notifier_error'] = [
                    'message' => 'Notifier execution failed',
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return $notified;
    }
}
