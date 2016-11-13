<?php
namespace Ant\Http;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class Environment implements IteratorAggregate,ArrayAccess
{
    /**
     * @var array
     */
    protected $serverParams = [];

    /**
     * 在“$_SERVER”中不是以“HTTP_”开头的Http头
     *
     * @var array
     */
    protected $special = [
        'CONTENT_TYPE' => 1,
        'CONTENT_LENGTH' => 1,
        'PHP_AUTH_USER' => 1,
        'PHP_AUTH_PW' => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE' => 1,
    ];

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
     * @param array $serverParams
     */
    public function __construct(array $serverParams)
    {
        $this->serverParams = $serverParams;
    }

    /**
     * 获取集合中的所有数据
     *
     * @return array
     */
    public function toArray()
    {
        return $this->serverParams;
    }

    /**
     * 从上下文环境中提取HTTP头
     *
     * @return array
     */
    public function createHeader()
    {
        $headers = [];
        foreach ($this->serverParams as $key => $value) {
            //提取HTTP头
            if (isset($this->special[$key]) || strpos($key, 'HTTP_') === 0) {
                $key = strtolower(str_replace('_', '-', $key));
                $key = (strpos($key, 'http-') === 0) ? substr($key, 5) : $key;
                $headers[$key] = explode(',', $value);
            }
        }

        return $headers;
    }

    /**
     * 从上下文环境中提取Cookie
     *
     * @return array
     */
    public function createCookie()
    {
        if(!$_COOKIE){
            parse_str(str_replace('; ', '&', $this->serverParams['COOKIE']), $_COOKIE);
        }

        return $_COOKIE;
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset,$value)
    {
        $this->serverParams[$offset] = $value;
    }

    /**
     * @param string $offset
     * @return null|mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset)
            ? $this->serverParams[$offset]
            : null;
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset,$this->serverParams);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        if($this->offsetExists($offset)){
            unset($this->serverParams[$offset]);
        }
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->serverParams);
    }
}