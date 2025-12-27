<?php

namespace PROLANCEE\Support\Classes\Crypto;

use PROLANCEE\Support\Classes\Http\DomainChecker;
use Illuminate\Support\Facades\Cache;
use Exception;

final class Encrypter
{
    /**
     * Encrypt a string using AES-128-CBC algorithm with a dynamic key.
     *
     * @param string $value Plaintext to encrypt.
     * @return string|null Base64 encoded result containing IV + ciphertext.
     */
    public static function encrypt(string $value): ?string
    {
        try {
            $ttlSeconds = 3600;
            DomainChecker::boot();
            DomainChecker::checkDomainBySameSite();

            $valueHash = hash('sha256', $value);
            $cacheKey = "encrypted_value:{$valueHash}";
            $lockKey = "lock:{$cacheKey}";

            $tries = 3;
            $waitMicroseconds = 200000; 

            while ($tries-- > 0) {
                $lock = Cache::lock($lockKey, 10); 

                if ($lock->get()) {
                    try {
                        $cached = Cache::get($cacheKey);
                        if ($cached) {
                            return $cached;
                        }

                        $key = self::getSecretKey();
                        $ivLength = openssl_cipher_iv_length('AES-128-CBC');
                        $iv = random_bytes($ivLength);
                        $encrypted = openssl_encrypt($value, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                        $result = base64_encode($iv . $encrypted);

                        Cache::put($cacheKey, $result, $ttlSeconds);

                        return $result;
                    } finally {
                        $lock->release();
                    }
                }
                usleep($waitMicroseconds);
            }
            $this->call('cache:clear');
            return $value;

        } catch (Exception $e) {
            return $value;
        }
    }

    /**
     * Generate a 16-byte secret key from the salt.
     *
     * @return string 16-byte key.
     */
    private static function getSecretKey(): string
    {
        $salt = self::generateSalt();
        $hash = hash('sha256', $salt, true);
        return substr($hash, 0, 16);        
    }

    /**
     * Salt string used for encryption key generation.
     *
     * @return string
     */
    private static function generateSalt(): string
    {
        return config('prolancee.support.encrypt_key', '*ActUo*!&#Env77~'); 
    }

    /**
     * Returns the encrypted value for a given flattened key.
     * Checks __val first, then __key if __val does not exist.
     * Only returns base64-like strings longer than 20 characters.
     *
     * @param array  $encryption Flattened encryption array
     * @param string $search     Key path to search (e.g., 'data.name_one')
     * @return string|bool       Encrypted value if found, otherwise false
     */
    public static function checkEncryptFlat(array $encryption, string $search): string|bool
    {
        foreach (["{$search}.__val", "{$search}.__key"] as $key) {
            if (!isset($encryption[$key])) continue;

            $val = $encryption[$key];
            
            if (preg_match('/^[A-Za-z0-9\/+=]+$/', $val) && strlen($val) > 20) {
                return $val;
            }
        }
        return false;
    }

    /**
     * Returns encrypted table name from flattened encryption array.
     * Only returns base64-like strings longer than 20 characters.
     *
     * @param array $encryption Flattened encryption array
     * @param string $tableName Original table name fallback
     * @param int $key Optional key index
     * @return string Encrypted value if found, otherwise $tableName
     */
    public static function getPayloadEncryptTable(array $encryption, string $tableName, int $key): string
    {
        return self::checkEncryptFlat($encryption, 'table')
            ?: self::checkEncryptFlat($encryption, "table.{$key}")
            ?: $tableName;
    }

    /**
     * Returns encrypted column name from flattened encryption array.
     * Only returns base64-like strings longer than 20 characters.
     *
     * @param array $encryption Flattened encryption array
     * @param string $tableName Table name fallback
     * @return string Encrypted value if found, otherwise fallback
     */
    public static function getPayloadEncryptColumn(array $encryption, string $tableName): string
    {
        return self::checkEncryptFlat($encryption, 'column')
            ?: self::checkEncryptFlat($encryption, "column.{$tableName}")
            ?: $tableName;
    }

    /**
     * Returns encrypted data field for a table from flattened encryption array.
     * Only returns base64-like strings longer than 20 characters.
     *
     * @param array $encryption Flattened encryption array
     * @param string $tableName Table name
     * @param string $field Field name
     * @return string Encrypted value if found, otherwise original field name
     */
    public static function getPayloadEncryptDataColumn(array $encryption, string $tableName, string $field): string
    {
        return self::checkEncryptFlat($encryption, "data.{$field}")
            ?: self::checkEncryptFlat($encryption, "data.{$tableName}.{$field}")
            ?: $field;
    }
}