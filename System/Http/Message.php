<?php
namespace Ant\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\MessageInterface;

/**
 * Class Message
 * @package Ant\Http
 */
abstract class Message implements MessageInterface{
    /**
     * @var bool 是否保持数据不变性
     */
    protected $immutability = true;

    /**
     * @var string HTTP版本号
     */
    protected $protocolVersion = '1.1';

    /**
     * HTTP头信息
     *
     * @var Header
     */
    protected $headers;

    /**
     * body信息
     *
     * @var StreamInterface
     */
    protected $body;

    /**
     * 获取HTTP协议版本
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * 设置HTTP协议版本
     *
     * @param $version
     * @return Message
     */
    public function withProtocolVersion($version)
    {
        $result = $this->immutability ? clone $this : $this;
        $result->protocolVersion = $version;

        return $result;
    }

    /**
     * 获取HTTP Header
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }


    /**
     * 检查header是否存在
     *
     * @param $name
     * @return bool
     */
    public function hasHeader($name)
    {
        $name = strtolower($name);

        return $this->headers->has($name);
    }

    /**
     * 返回指定header数组
     *
     * @param $name
     * @return string[]
     */
    public function getHeader($name)
    {
        $name = strtolower($name);

        if(!$this->hasHeader($name)){
            return [];
        }

        return $this->headers->get($name);
    }

    /**
     * 返回一行header的值
     *
     * @param $name
     * @return string
     */
    public function getHeaderLine($name)
    {
        $value = $this->getHeader($name);

        return !empty($value) ? implode(',',$value) : '';
    }

    /**
     * 替换之前header
     *
     * @param $name
     * @param $value
     * @return Message
     */
    public function withHeader($name, $value)
    {
        $result = $this->immutability ? clone $this : $this;

        $value = is_array($value) ? $value : explode(',',$value);
        $result->headers->set($name,$value);

        return $result;
    }

    /**
     * 向header添加信息
     *
     * @param $name  string
     * @param $value string||string[]
     * @return Message
     */
    public function withAddedHeader($name, $value)
    {
        if($this->hasHeader($name)){
            $value = (is_array($value))
                ? array_merge($this->getHeader($name),$value)
                : $value = implode(',',$this->getHeader($name)).','.$value;
        }

        return $this->withHeader($name, $value);
    }

    /**
     * 销毁header信息
     *
     * @param $name
     * @return $this|Message
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $result = $this->immutability ? clone $this : $this;
        $name = strtolower($name);

        $result->headers->remove($name);

        return $result;
    }

    /**
     * 获取body
     *
     * @return StreamInterface
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * 添加body数据
     *
     * @param StreamInterface $body
     * @return $this|Message
     */
    public function withBody(StreamInterface $body)
    {
        if ($body === $this->body) {
            return $this;
        }

        $result = $this->immutability ? clone $this : $this;
        $result->body = $body;

        return $result;
    }
}