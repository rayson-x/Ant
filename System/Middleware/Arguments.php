<?php
namespace Ant\Middleware;

use ArrayAccess;

/**
 * 在中间件中的传递参数
 *
 * Class Arguments
 * @package Ant\Middleware
 * @see http://php.net/manual/zh/class.arrayaccess.php
 */
class Arguments implements ArrayAccess
{
    private $arguments;

    /**
     * 注册传递的参数
     *
     * Arguments constructor.
     * @param array $param
     */
    public function __construct($param)
    {
        $this->arguments = is_array($param) ? $param : func_get_args();
    }

    /**
     * 获取参数
     *
     * @return array
     */
    public function toArray()
    {
        return $this->arguments;
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset,$this->arguments);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->arguments[$offset] : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->arguments[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->arguments[$offset]);
    }
}