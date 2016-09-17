<?php
namespace Ant\Http;

use RuntimeException;

class Exception extends RuntimeException
{
    public static function factory($status, \Exception $previous = null)
    {
        return new self(StatusPhrase::getStatusPhrase($status), $status, $previous);
    }
}