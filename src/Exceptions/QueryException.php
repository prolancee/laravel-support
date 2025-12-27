<?php

namespace PROLANCEE\Support\Exceptions;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Throwable;
use Exception;

class QueryException extends Exception
{
    /**
     * Process a queryException and return a formatted array response.
     *
     * @return array
     */
    public static function queryException(Throwable $exception, $dbType = null)
    {
        // Get the default database type if not provided
        if (!$dbType) {
            $dbType = Config::get('database.default'); 
        }
    
        // Check if the exception contains errorInfo (SQL-based errors)
        if (property_exists($exception, 'errorInfo') && isset($exception->errorInfo)) {
        $errorInfo = $exception->errorInfo;
        $errorCode = isset($errorInfo[1]) ? $errorInfo[1] : null;
        $message = isset($errorInfo[2]) ? $errorInfo[2] : $exception->getMessage();

        $title = 'A database error occurred';
        $code = 500;

        // Define error messages for different database types
        $errorMessages = [];

        if ($dbType === 'mysql') {
            $errorMessages = [
                // No database
                1046 => ['No database.', 500],

                // Database Errors
                1049 => ['Unknown database.', 500],  // Database does not exist

                // Table Errors
                1146 => ['Table not found.', 500],  // Table does not exist

                // Column Errors
                1054 => ['Column not found in the database.', 500],  // Column does not exist

                // Duplicate Entry
                1062 => ['Duplicate entry detected.', 400],  // Unique constraint

                // Foreign Key Violations
                1451 => ['Cannot delete this record due to a foreign key constraint.', 400],  
                1452 => ['Cannot add or update a child row due to a foreign key constraint.', 400],  

                // SQL Syntax Errors
                1064 => ['SQL syntax error.', 400],  

                // Lock & Deadlock Issues
                1205 => ['Lock wait timeout exceeded.', 500],  
                1213 => ['Deadlock found when trying to get lock.', 500],  

                // Permissions & Authentication
                1142 => ['Permission denied for SELECT command.', 403],  

                // Constraint Errors
                1364 => ['Field does not have a default value.', 400],  
            ];
        } elseif ($dbType === 'pgsql') {
            $errorMessages = [
                // No database
                '3D000' => ['No database.', 500],

                // Database Errors
                '3D000' => ['Database does not exist or is incorrect.', 500],  

                // Table Errors
                '42P01' => ['Undefined table.', 500],  

                // Column Errors
                '42703' => ['Undefined column.', 500],  

                // Duplicate Entry
                '23505' => ['Unique violation (duplicate key).', 400],  

                // Foreign Key Violations
                '23503' => ['Foreign key violation.', 400],  

                // Permissions & Authentication
                '28000' => ['Invalid authorization specification.', 403],  
            ];
        } elseif ($dbType === 'sqlsrv') {
            $errorMessages = [
                // No database
                4060 => ['No database.', 500],  

                // Database Errors
                4060 => ['Database does not exist or is incorrect.', 500],  

                // Table Errors
                208  => ['Invalid object name (table not found).', 500],  

                // Column Errors
                207  => ['Invalid column name.', 500],  

                // Duplicate Entry
                2627 => ['Duplicate key violation (PRIMARY KEY or UNIQUE constraint).', 400],  
                2601 => ['Cannot insert duplicate key in a unique index.', 400],  

                // Foreign Key Violations
                547  => ['Foreign key violation.', 400],  

                // Lock & Deadlock Issues
                1205 => ['Deadlock victim (deadlock found).', 500],  

                // Permissions & Authentication
                18456 => ['Login failed for user.', 403],  
                229   => ['Permission denied for the requested action.', 403],  

                // Syntax & Query Errors
                102  => ['Incorrect syntax near token.', 400],  
                137  => ['Must declare the scalar variable.', 400],  

                // Constraint Errors
                8152 => ['String or binary data would be truncated.', 400],  
                2714 => ['Table already exists.', 400],  
            ];
        } elseif ($dbType === 'sqlite') {
            $errorMessages = [
                // No database
                1 => ['No database.', 500],

                // Database Errors
                1  => ['SQL error or missing database.', 500],  
                5  => ['Database is locked.', 500],  

                // Table Errors
                6  => ['No such table.', 500],  

                // Duplicate Entry & Constraints
                19 => ['Constraint failed (duplicate or foreign key violation).', 400],  

                // Syntax Errors
                21 => ['SQL logic error or malformed query.', 400],  
            ];
        }

        // Check if the error code exists in predefined error messages
        if (isset($errorMessages[$errorCode])) {
            [$title, $code] = $errorMessages[$errorCode];
        }

            return [
                'code'    => $code,
                'title'     => $title,
                'message'   => $message,
                'errorCode' => $errorCode,
                'dbType'    => $dbType,
                'error'     => 'query_exception',
            ];
        }
    
        // Handle Redis-specific errors
        if ($dbType === 'redis') {
        $message = $exception->getMessage();
        $title = 'Redis Error';
        $code = 500;

        if (str_contains($message, 'Connection refused')) {
            $title = 'Redis Connection Error';
            $code = 500;
        } elseif (str_contains($message, 'NOAUTH')) {
            $title = 'Redis Authentication Failed';
            $code = 403;
        } elseif (str_contains($message, 'timeout')) {
            $title = 'Redis Timeout Error';
            $code = 500;
        } elseif (str_contains($message, 'WRONGTYPE')) {
            $title = 'Redis Wrong Key Type Error';
            $code = 400;
        }
            return [
                'status'  => false,
                'code'  => $code,
                'title'   => $title,
                'message' => $message,
                'dbType'  => $dbType,
                'error'   => 'redis_exception',
            ];
        }
     
        // If the exception is not a query-related error, return a generic error
        return [
            'status'  => false,
            'code'    => 500,
            'title'   => 'Whoops! Something went wrong.',
            'message' => $exception->getMessage(),
            'error'   => 'Whoops!',
        ];
    }     
}