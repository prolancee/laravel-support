<?php

namespace PROLANCEE\Support\Exceptions;

use Throwable;
use Exception;

/**
 * Custom Exception to handle scenarios where a function or value already exists.
 */
class ExitsFunException extends Exception
{
    /**
     * Constructor for ExitsFunException.
     *
     * @param string $message - The exception message.
     * @param int $code - The exception code (default: 0).
     * @param Exception|null $previous - Previous exception for chaining.
     */
    public function __construct($message = "Function or value already exists.", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Convert the exception to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
