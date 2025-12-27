<?php

namespace PROLANCEE\Support\Classes\Database;

use Illuminate\Support\Facades\{DB, Storage};
use PROLANCEE\Support\Classes\Crypto\Encrypter;
use Exception;
use Throwable;

final class DBEncrypter
{
    protected const FILE_PATH_RDBMS = 'prolancee/rdbms.json';
    protected const FILE_PATH_REDIS = 'prolancee/redis.json';

    /**
     * Encrypt table names and columns for the default database connection.
     *
     * @return bool True on success, false on failure
     */
    public static function setTableColumnEncryption(bool $force = false): bool
    {
        try {
            // Force condition
            if (Storage::exists(self::FILE_PATH_RDBMS) && ! $force) {
                return true;
            }

            $databaseConnection = config('database.default');
            $databaseName = config("database.connections.{$databaseConnection}.database");

            if (! $databaseName) return false;

            $connection = DB::connection($databaseConnection);
            [$tables, $tableKey] = self::getTables($connection, $databaseConnection, $databaseName);

            $encryptedData = [];

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                $encryptedTableName = Encrypter::encrypt($tableName);

                $encryptedData[$tableName] = [
                    $encryptedTableName => []
                ];

                $columns = self::getColumns($connection, $databaseConnection, $tableName);

                foreach ($columns as $column) {
                    $columnName = $column->Field ?? $column->column_name ?? $column->name;
                    $encryptedColumnName = Encrypter::encrypt($columnName);
                    $encryptedData[$tableName][$encryptedTableName][$columnName] = $encryptedColumnName;
                }
            }

            Storage::makeDirectory(dirname(self::FILE_PATH_RDBMS));
            Storage::put(
                self::FILE_PATH_RDBMS,
                json_encode($encryptedData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
            );

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Encrypt both database schema and Redis configuration.
     *
     * @return bool True on success, false on failure
     */
    public static function setDatabaseAndRedisEncryption(bool $force = false): bool
    {
        try {
            // Force condition
            if (Storage::exists(self::FILE_PATH_REDIS) && ! $force) {
                return true;
            }

            $encryptedData = [
                'redis'    => [],
                'database' => []
            ];

            // Encrypt Redis config
            $redisConfig = config('database.redis');
            foreach ($redisConfig as $connection => $value) {
                if (is_array($value)) {
                    $encryptedData['redis'][$connection] = [];
                    foreach ($value as $key => $subValue) {
                        $encryptedData['redis'][$connection][$key] = Encrypter::encrypt($subValue ?? '');
                    }
                } else {
                    $encryptedData['redis'][$connection] = Encrypter::encrypt($value ?? '');
                }
            }

            // Encrypt DB tables and columns
            $databaseConnection = config('database.default');
            $databaseName = config("database.connections.{$databaseConnection}.database");

            if (! $databaseName) return false;

            $connection = DB::connection($databaseConnection);
            [$tables, $tableKey] = self::getTables($connection, $databaseConnection, $databaseName);

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                $encryptedTableName = Encrypter::encrypt($tableName);

                $encryptedData['database'][$tableName] = [
                    'original'  => $tableName,
                    'encrypted' => $encryptedTableName,
                    'columns'   => []
                ];

                $columns = self::getColumns($connection, $databaseConnection, $tableName);

                foreach ($columns as $column) {
                    $columnName = $column->Field ?? $column->column_name ?? $column->name;
                    $encryptedColumnName = Encrypter::encrypt($columnName);

                    $encryptedData['database'][$tableName]['columns'][$columnName] = [
                        'original'  => $columnName,
                        'encrypted' => $encryptedColumnName
                    ];
                }
            }

            Storage::makeDirectory(dirname(self::FILE_PATH_REDIS));
            Storage::put(
                self::FILE_PATH_REDIS,
                json_encode($encryptedData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
            );

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get previously encrypted DB schema.
     *
     * @param string|null $tableName
     * @return array
     */
    public static function getDatabaseEncryption(?string $tableName = null): array
    {
        if (!Storage::exists(self::FILE_PATH_RDBMS)) {
            return [];
        }

        $data = json_decode(Storage::get(self::FILE_PATH_RDBMS), true);
        return $tableName ? ($data[$tableName] ?? []) : $data;
    }

    /**
     * Get previously encrypted Redis configuration.
     *
     * @return array
     */
    public static function getRedisEncryption(): array
    {
        if (!Storage::exists(self::FILE_PATH_REDIS)) {
            return [];
        }

        $data = json_decode(Storage::get(self::FILE_PATH_REDIS), true);
        return $data['redis'] ?? [];
    }

    /**
     * Helper: Get all tables for a given DB connection.
     *
     * @param mixed  $connection
     * @param string $driver
     * @param string $dbName
     * @return array{0: array<int, object>, 1: string}
     * @throws Exception
     */
    private static function getTables($connection, string $driver, string $dbName): array
    {
        return match ($driver) {
            'mysql', 'sqlsrv' => [$connection->select('SHOW TABLES'), 'Tables_in_' . $dbName],
            'pgsql'           => [$connection->select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'"), 'table_name'],
            'sqlite'          => [$connection->select("SELECT name FROM sqlite_master WHERE type='table'"), 'name'],
            default           => throw new Exception('Unsupported database connection type.')
        };
    }

    /**
     * Helper: Get columns of a table for a given connection.
     *
     * @param mixed  $connection
     * @param string $driver
     * @param string $tableName
     * @return array<int, object>
     */
    private static function getColumns($connection, string $driver, string $tableName): array
    {
        return match ($driver) {
            'sqlite'  => $connection->select("PRAGMA table_info($tableName)"),
            'mysql'   => $connection->select("SHOW COLUMNS FROM `$tableName`"),
            'pgsql'   => $connection->select("SELECT column_name FROM information_schema.columns WHERE table_name = ?", [$tableName]),
            'sqlsrv'  => $connection->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?", [$tableName]),
            default   => []
        };
    }
}
