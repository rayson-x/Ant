<?php
namespace Ant;

use Ant\Interfaces\CollectionInterface;
use \ArrayIterator;

class Collection implements CollectionInterface
{
    protected $items = [];

    public function __construct(array $items){
        foreach($items as $offset => $item){
            $this->set($offset,$item);
        }
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
        foreach($items as $key => $value){
            $this->set($key,$value);
        }
    }

    /**
     * 获取集合中的所有数据
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
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
     * ArrayAccess预定义接口,可以将对象以数组的方式使用
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset,$value);
    }

    /**
     * ArrayAccess预定义接口
     *
     * @param mixed $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * ArrayAccess预定义接口
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * ArrayAccess预定义接口
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Countable预定义接口,获取数据集总数
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * IteratorAggregate预定义接口,返回外部迭代器
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

}