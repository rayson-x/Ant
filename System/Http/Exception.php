<?php
namespace Ant\Http;

use RuntimeException;

class Exception extends RuntimeException
{
    public function __construct($status, $message = null ,\Exception $previous = null)
    {
        return parent::__construct($message, $status, $previous);
    }
}