<?php
namespace Ant\Http;

/**
 * Class Message
 * @package Ant\Http
 */
abstract class Message implements \Psr\Http\Message\MessageInterface{
    /**
     * @var bool �Ƿ񱣳����ݲ�����
     */
    protected $immutability = true;

    /**
     * @var string HTTP�汾��
     */
    protected $protocolVersion = '1.1';

    /**
     * @var array HTTPͷ��Ϣ
     */
    protected $headers = [];

    /**
     * @var \Psr\Http\Message\StreamInterface body��Ϣ
     */
    protected $body;

    /**
     * ��ȡHTTPЭ��汾
     * @return string
     */
    public function getProtocolVersion(){
       return $this->protocolVersion;
    }

    /**
     * ����HTTPЭ��汾
     * @param $version
     * @return Message
     */
    public function withProtocolVersion($version){
        $result = $this->immutability ? clone $this : $this;
        $result->protocolVersion = $version;

        return $result;
    }

    /**
     * ��ȡHTTP Header
     * @return array
     */
    public function getHeaders(){
        return $this->headers;
    }


    /**
     * ���header�Ƿ����
     * @param $name
     * @return bool
     */
    public function hasHeader($name){
        $name = strtolower($name);

        return array_key_exists($name, $this->headers);
    }

    /**
     * ��ȡָ��header,Ϊ�շ��ؿ�����
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
     * ��ȡָ��header��ֵ,Ϊ�շ��ؿ��ַ���
     * @param $name
     * @return string
     */
    public function getHeaderLine($name){
        $value = $this->getHeader($name);

        return !empty($value) ? implode(',',$value) : '';
    }

    /**
     * �滻֮ǰheader
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
     * ��header�����Ϣ
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
     * ����header��Ϣ
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
     * ��ȡbody
     * @return \Psr\Http\Message\StreamInterface
     */
    public function getBody(){
        return $this->body;
    }

    /**
     * ���body����
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