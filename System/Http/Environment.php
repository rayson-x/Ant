<?php
namespace Ant\Http;

use ArrayIterator;
use IteratorAggregate;

class Environment implements IteratorAggregate
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * 模拟Http请求
     *
     * @param array $userData
     * @return static
     */
    public static function mock(array $userData = [])
    {
        $data = array_merge([
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'SCRIPT_NAME'          => '',
            'REQUEST_URI'          => '',
            'QUERY_STRING'         => '',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'Ant',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REQUEST_TIME'         => time(),
            'REQUEST_TIME_FLOAT'   => microtime(true),
        ], $userData);

        return new static($data);
    }

    /**
     * Environment constructor.
     * @param array $items
     */
    public function __construct($items)
    {
        $items = is_array($items) ? $items : func_get_args();

        $this->items = $items;
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

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}