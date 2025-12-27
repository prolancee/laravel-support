<?php

namespace PROLANCEE\Support\Classes\Crypto;

use Throwable;

final class Decrypter
{
    /**
     * Decrypt a base64 encoded string (IV + encrypted) with multi-part support.
     *
     * Handles:
     * - Strings containing multiple '.' separated encrypted parts
     * - Encrypted substrings anywhere in the string
     * - Optional suffixes like "<...>" at the end
     *
     * @param string $encoded
     * @return string|null
     */
    public static function decrypt(string $encoded): ?string
    {
        try {
            $key = self::getSecretKey();
            $ivLength = openssl_cipher_iv_length('AES-128-CBC');

            $suffix = '';
            if (preg_match('/<(.*?)>$/', $encoded, $matches)) {
                $suffix  = $matches[0];
                $encoded = substr($encoded, 0, -strlen($suffix));
            }

            $decoded = preg_replace_callback(
                '/[A-Za-z0-9+\/=]{20,}/', 
                function ($matches) use ($key, $ivLength) {
                    return self::singleDecrypt($matches[0], $key, $ivLength);
                },
                $encoded
            );

            return $decoded . $suffix;

        } catch (Throwable $e) {
            return $encoded;
        }
    }

    /**
     * Single string decrypt
     */
    private static function singleDecrypt(string $encoded, string $key, int $ivLength): string
    {
        $ciphertext = base64_decode($encoded, true);
        if ($ciphertext === false) {
            return $encoded; 
        }

        $iv = substr($ciphertext, 0, $ivLength);
        $encrypted = substr($ciphertext, $ivLength);

        $plain = openssl_decrypt($encrypted, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain !== false ? $plain : $encoded;
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
}