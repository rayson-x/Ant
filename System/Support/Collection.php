<?php
namespace Ant\Support;

use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected $items = [];

    public function __construct($items = [])
    {
        $this->replace(
            is_array($items) ? $items : func_get_args()
        );
    }

    /**
     * 设置数据集
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * 通过key从数据集中取得数据
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->items[$key] : $default;
    }

    /**
     * 替换现有数据集
     *
     * @param array $items
     */
    public function replace(array $items)
    {
        foreach($items as $offset => $item){
            $this->set($offset,$item);
        }
    }

    /**
     * 通过key检查数据是否存在于数据集之中
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * 通过key删除指定数据
     *
     * @param $key
     */
    public function remove($key)
    {
        if($this->has($key)){
            unset($this->items[$key]);
        }
    }

    /**
     * 重置数据集
     */
    public function reset()
    {
        $this->items = [];
    }

    /**
     * 获取集合中的所有数据
     *
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * 转换为JSON数据
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name,$value)
    {
        $this->set($name,$value);
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset,$value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}