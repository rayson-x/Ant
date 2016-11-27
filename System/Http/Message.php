<?php
namespace Ant\Http;

use InvalidArgumentException;
use Ant\Http\Message\XmlRenderer;
use Ant\Http\Message\JsonRenderer;
use Ant\Http\Message\FileRenderer;
use Ant\Http\Message\HtmlRenderer;
use Ant\Http\Message\JsonpRenderer;
use Psr\Http\Message\StreamInterface;
use Ant\Http\Interfaces\MessageInterface;
use Ant\Http\Interfaces\RendererInterface;
use Ant\Http\Exception\NotAcceptableException;

/**
 * Class Message
 * @package Ant\Http
 */
abstract class Message implements MessageInterface
{
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
     * @var array
     */
    protected $headers;

    /**
     * body信息
     *
     * @var StreamInterface
     */
    protected $body;

    /**
     * 响应格式
     *
     * @var mixed
     */
    protected $contentType = 'html';

    /**
     * 装饰器列表
     *
     * @var array
     */
    protected $renderer = [
        'xml'   =>  XmlRenderer::class,
        'file'  =>  FileRenderer::class,
        'json'  =>  JsonRenderer::class,
        'html'  =>  HtmlRenderer::class,
        'js'    =>  JsonpRenderer::class,
    ];

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
        return $this->changeAttribute('protocolVersion',$version);
    }

    /**
     * 获取HTTP Header
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
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

        return array_key_exists($name,$this->headers);
    }

    /**
     * 返回指定header数组
     *
     * @param $name
     * @return array
     */
    public function getHeader($name)
    {
        $name = strtolower($name);

        if(!$this->hasHeader($name)){
            return [];
        }

        return $this->headers[$name];
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
     * @return self
     */
    public function withHeader($name, $value)
    {
        return $this->changeAttribute(['headers',strtolower($name)],is_array($value) ? $value : explode(',',$value));
    }

    /**
     * 向header添加信息
     *
     * @param $name  string
     * @param $value string|array
     * @return self
     */
    public function withAddedHeader($name, $value)
    {
        if($this->hasHeader($name)){
            $value = (is_array($value))
                ? array_merge($this->getHeader($name),$value)
                : implode(',',$this->getHeader($name)).','.$value;
        }

        return $this->withHeader($name, $value);
    }

    /**
     * 销毁header信息
     *
     * @param $name
     * @return self
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }
        $header = $this->headers;
        unset($header[strtolower($name)]);

        return $this->changeAttribute('headers',$header);
    }

    /**
     * 通过迭代的方式添加响应头
     *
     * @param $iterator \Iterator|array
     * @return self
     */
    public function addHeaderFromIterator($iterator)
    {
        if(!$iterator instanceof \Iterator && !is_array($iterator)){
            throw new \RuntimeException('');
        }

        $self = $this;
        foreach($iterator as $name => $value){
            $self = $self->withAddedHeader($name,$value);
        }

        return $self;
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

        return $this->changeAttribute('body',$body);
    }

    /**
     * 选择装饰器
     *
     * @param $type
     * @return RendererInterface
     */
    public function selectRenderer($type)
    {
        if(!is_string($type)){
            throw new InvalidArgumentException('type must be a string');
        }

        if(!array_key_exists($type,$this->renderer)){
            throw new NotAcceptableException('Decorative device does not exist');
        }

        $this->contentType = $type;

        return new $this->renderer[$this->contentType];
    }

    /**
     * @param string $type
     * @param RendererInterface $renderer
     * @return $this
     */
    public function setRenderer($type,RendererInterface $renderer)
    {
        $this->renderer[$type] = $renderer;

        return $this;
    }

    /**
     * 设置对象不变性
     * 根据PSR-7的接口要求
     * 每次修改请求内容或者响应内容
     * 都要保证原有数据不能被覆盖
     * 所以在改变了一项属性的时候需要clone一个相同的类
     * 去改变那个相同的类的属性，通过这种方式保证原有数据不被覆盖
     * 本人出于损耗与易用性，给这个保持不变性加上了一个开关
     *
     * @param bool|false $enable
     * @return self
     */
    public function keepImmutability($enable = true)
    {
        $this->immutability = $enable;

        return $this;
    }

    /**
     * 输出Http头字符串
     *
     * @return string
     */
    protected function headerToString()
    {
        $result = [];

        foreach($this->getHeaders() as $headerName => $headerValue){
            if (is_array($headerValue)) {
                $headerValue = implode(',', $headerValue);
            }

            $headerName = implode('-',array_map('ucfirst',explode('-',$headerName)));
            $result[] = sprintf('%s: %s',$headerName,$headerValue);
        }

        return implode(PHP_EOL,$result).PHP_EOL;
    }

    /**
     * 保持数据不变性
     *
     * @param $attribute string
     * @param $value mixed
     * @return self
     */
    protected function changeAttribute($attribute,$value)
    {
        $result = $this->immutability ? clone $this : $this;
        if(is_array($attribute)){
            list($array,$key) = $attribute;

            // 兼容5.6
            $array = &$result->$array;
            $array[$key] = $value;
        }else{
            $result->$attribute = $value;
        }

        return $result;
    }
}