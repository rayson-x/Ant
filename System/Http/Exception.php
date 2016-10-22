<?php
namespace Ant\Http;

use RuntimeException;

/**
 * Http Exception
 *
 * Class Exception
 * @package Ant\Http
 */
class Exception extends RuntimeException
{
    public function __construct($status, $message = null ,\Exception $previous = null)
    {
        return parent::__construct($message, $status, $previous);
    }
}