<?php
namespace Ant\Middleware;

use ArrayAccess;

class Arguments implements ArrayAccess
{
    private $arguments;

    public function __construct(...$param)
    {
        $this->arguments = $param;
    }

    public function toArray()
    {
        return $this->arguments;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset,$this->arguments);
    }

    public function offsetGet($offset)
    {
        return $this->arguments[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->arguments[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->arguments[$offset]);
    }
}