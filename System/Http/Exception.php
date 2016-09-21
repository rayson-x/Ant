<?php
namespace Ant\Http;

use RuntimeException;

class Exception extends RuntimeException
{
    public function __construct($status, \Exception $previous = null)
    {
        return parent::__construct(StatusPhrase::getStatusPhrase($status), $status, $previous);
    }
}