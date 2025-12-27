<?php

use PROLANCEE\Support\Classes\Crypto\Encrypter;
use PROLANCEE\Support\Classes\Crypto\Decrypter;
use PROLANCEE\Support\Exceptions\ExitsFunException;

try {
    // Handle secure_encode function definition
    if (!function_exists('secure_encode')) 
    {
        /**
         * Securely encode a given value using the custom Encrypter class
         *
         * @param mixed $value - The value to be encoded
         * @return string - Encoded string
         */
        function secure_encode($value) 
        {
            if($value)
            {
                return Encrypter::encrypt($value);
            }
            return null;
        }

    } else {
        throw new ExitsFunException("The function 'secure_encode' already exists.");
    }

    // Handle secure_decode function definition
    if (!function_exists('secure_decode')) 
    {
        /**
         * Decode a previously encoded value using the custom Encrypter class
         *
         * @param string $encoded - The encoded string
         * @return mixed - Original value after decoding
         */
        function secure_decode($encoded) 
        {
            if($encoded)
            {
              return Decrypter::decrypt($encoded);
            }
            return null;
        }

    } else {
        throw new ExitsFunException("The function 'secure_decode' already exists.");
    }

} catch (ExitsFunException $e) {
    echo $e->getMessage();
}
