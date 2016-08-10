<?php
namespace Ant\Http;

/**
 * Class Message
 * @package Ant\Http
 */
abstract class Message implements \Psr\Http\Message\MessageInterface{
    /**
     * @var bool 是否保持数据不变性
     */
    protected $immutability = true;

    /**
     * @var string HTTP版本号
     */
    protected $protocolVersion = '1.1';

    /**
     * @var array HTTP头信息
     */
    protected $headers = [];

    /**
     * @var \Psr\Http\Message\StreamInterface body信息
     */
    protected $body;

    /**
     * 获取HTTP协议版本
     * @return string
     */
    public function getProtocolVersion(){
       return $this->protocolVersion;
    }

    /**
     * 设置HTTP协议版本
     * @param $version
     * @return Message
     */
    public function withProtocolVersion($version){
        $result = $this->immutability ? clone $this : $this;
        $result->protocolVersion = $version;

        return $result;
    }

    /**
     * 获取HTTP Header
     * @return array
     */
    public function getHeaders(){
        return $this->headers;
    }


    /**
     * 检查header是否存在
     * @param $name
     * @return bool
     */
    public function hasHeader($name){
        $name = strtolower($name);

        return array_key_exists($name, $this->headers);
    }

    /**
     * 获取指定header,为空返回空数组
     * @param $name
     * @return array
     */
    public function getHeader($name){
        $name = strtolower($name);

        if(!$this->hasHeader($name))
            return [];

        return $this->headers[$name];
    }

    /**
     * 获取指定header的值,为空返回空字符串
     * @param $name
     * @return string
     */
    public function getHeaderLine($name){
        $value = $this->getHeader($name);

        return !empty($value) ? implode(',',$value) : '';
    }

    /**
     * 替换之前header
     * @param $name
     * @param $value
     * @return Message
     */
    public function withHeader($name, $value){
        $result = $this->immutability ? clone $this : $this;
        $name = strtolower($name);

        $value = is_array($value) ? $value : [$value];
        $result->headers[$name] = $value;

        return $result;
    }

    /**
     * 向header添加信息
     * @param $name
     * @param $value
     * @return Message
     */
    public function withAddedHeader($name, $value){
        if($values = $this->getHeader($name)){
            $values[] = $value;
        }else{
            $values = $value;
        }

        return $this->withHeader($name, $values);
    }

    /**
     * 销毁header信息
     * @param $name
     * @return $this|Message
     */
    public function withoutHeader($name){
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $result = $this->immutability ? clone $this : $this;
        $name = strtolower($name);

        unset($result->headers[$name]);

        return $result;
    }

    /**
     * 获取body
     * @return \Psr\Http\Message\StreamInterface
     */
    public function getBody(){
        return $this->body;
    }

    /**
     * 添加body数据
     * @param \Psr\Http\Message\StreamInterface $body
     * @return $this|Message
     */
    public function withBody(\Psr\Http\Message\StreamInterface $body)
    {
        if ($body === $this->body) {
            return $this;
        }

        $result = $this->immutability ? clone $this : $this;
        $result->body = $body;

        return $result;
    }
}