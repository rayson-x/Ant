<?php
namespace Ant\Http;

use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\UriInterface;
use \Psr\Http\Message\StreamInterface;
use \Ant\Collection;
use \InvalidArgumentException;
use \RuntimeException;

/**
 * Class Request
 * @package Ant\Http
 * 可能改变实例的所有方法都必须保证请求实例不能被改变,使得它们保持当前消息的内部状态,并返回一个包含改变状态的实例.
 */
class Request extends Message implements ServerRequestInterface{
    /**
     * 请求资源
     *
     * @var string
     */
    protected $requestTarget;
    /**
     * http 请求方式
     *
     * @var string
     */
    protected $method;
    /**
     * Uri 实例
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;
    /**
     * 服务器和执行环境信息
     *
     * @var array
     */
    protected $serverParams;
    /**
     * cookie参数
     *
     * @var array
     */
    protected $cookieParams;
    /**
     * 查询参数
     *
     * @var array
     */
    protected $queryParams;
    /**
     * http上传文件 \Psr\Http\Message\UploadedFileInterface 实例
     *
     * @var array
     */
    protected $uploadFiles;
    /**
     * body 参数
     *
     * @var array|object|null
     */
    protected $bodyParsed;
    /**
     * body 解析器 根据subtype进行调用
     *
     * @var callable[]
     */
    protected $bodyParsers = [];
    /**
     * @var array 属性
     */
    protected $attributes = [];


    public function __construct(Collection $server){
        $this->uri = Uri::createFromCollection($server);
        $this->serverParams = $server->all();
        $this->headers = Header::createFromCollection($server)->all();
        $this->cookieParams = $_COOKIE;
        $this->uploadFiles = UploadedFile::parseUploadedFiles($_FILES);
        $this->body = new RequestBody();

        $type = ['application/x-www-form-urlencoded','multipart/form-data'];
        if($server['REQUEST_METHOD'] === 'POST' && in_array($this->getContentType(),$type)){
            $this->bodyParsed = $_POST;
        }

        /* 加载默认body解析器 */
        $this->setBodyParsers('json',function($input){
            return json_decode($input,true);
        });

        $this->setBodyParsers('xml',function($input){
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            return $result;
        });

        $this->setBodyParsers('x-www-form-urlencoded',function($input){
            parse_str($input,$data);
            return $data;
        });
    }

    //获取请求目标(资源)
    public function getRequestTarget(){
        return $this->serverParams['REQUEST_URI'] ?:'/';
    }

    //设置请求资源,需保持数据不变性
    public function withRequestTarget($requestTarget){
        $result = clone $this;

        $result->serverParams['REQUEST_URI'] = $requestTarget;

        return $result;
    }

    //获取请求方式
    public function getMethod(){
        if($this->method){
            return $this->method;
        }

        $method = isset($this->serverParams['REQUEST_METHOD']) ? strtoupper($this->serverParams['REQUEST_METHOD']) : 'GET';
        if ($method !== 'POST') {
            return $this->method = $method;
        }

        $override = $this->getHeaderLine('x-http-method-override') ?: $this->getParsedBody();


    }

    //设置请求方式,需保持数据不变性
    public function withMethod($method){

    }

    //获取uri,返回\Psr\Http\Message\UriInterface实例
    public function getUri(){
        return $this->uri;
    }

    //设置uri,需要保持数据不变性
    public function withUri(\Psr\Http\Message\UriInterface $uri,$preserveHost = false){

    }

    //检索服务器参数
    public function getServerParams(){
        return $this->serverParams;
    }

    //检索Cookie参数,返回Array
    public function getCookieParams(){

    }

    //设置cookie参数,保持数据不变
    public function withCookieParams(array $cookies){

    }

    /**
     * 获取查询参数
     *
     * @return array
     */
    public function getQueryParams()
    {
        if(is_array($this->queryParams)){
            return $this->queryParams;
        }

        if ($this->uri === null) {
            return [];
        }

        parse_str($this->uri->getQuery(),$this->queryParams);

        return $this->queryParams;
    }

    //添加查询参数,保持数据不变性
    public function withQueryParams(array $query)
    {
        $result = clone $this;
        $result->queryParams = array_merge($result->getQueryParams(),$query);

        return $result;
    }

    //获取上传文件信息,需返回数组
    public function getUploadedFiles(){

    }

    //添加上传文件信息
    public function withUploadedFiles(array $uploadedFiles){

    }

    /**
     * 获取body解析结果
     *
     * @return array|null|object
     */
    public function getParsedBody()
    {
        if($this->bodyParsed){
            return $this->bodyParsers;
        }

        if(!$this->body){
            return null;
        }

        $contentType = $this->getContentType();
        $parts = explode('/',$contentType);
        $type = array_shift($parts);
        $subtype = array_pop($parts);

        if(in_array(strtolower($type),['application','text']) && isset($this->bodyParsers[$subtype])){
            $body = (string)$this->getBody();
            $parsed = call_user_func($this->bodyParsers[$subtype],$body);

            if (!(is_null($parsed) || is_object($parsed) || is_array($parsed))){
                throw new RuntimeException('Request body media type parser return value must be an array, an object, or null');
            }
            $this->bodyParsed = $parsed;
            return $this->bodyParsed;
        }

        return null;
    }

    //输入数据只能为null,数组,对象,保持数据不变性
    public function withParsedBody($data)
    {
        if(!(is_null($data) || is_array($data) || is_object($data))){
            throw new InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        $result = clone $this;
        $result->bodyParsed = $data;

        return $result;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return mixed[] Attributes derived from the request.
     */
    public function getAttributes(){

    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null){

    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value){

    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name)
    {

    }

    public function isMethod($method)
    {
        return $this->getMethod() === $method;
    }

    public function isGet()
    {
        return $this->isMethod('GET');
    }

    public function isPost()
    {
        return $this->isMethod('POST');
    }

    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    public function isAjax()
    {
        return strtolower($this->getHeaderLine('x-requested-with')) === 'xmlhttprequest';
    }

    public function get()
    {
    }

    public function post()
    {

    }

    /**
     * 获取content-type
     *
     * @return null
     */
    public function getContentType()
    {
        $result = $this->getHeader('content-type');

        $contentType = $result ? $result[0] : null;
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;

    }

    /**
     * 设置body解析器
     *
     * @param $subtype string
     * @param $parsers callable
     */
    public function setBodyParsers($subtype,$parsers)
    {
        if(!is_callable($parsers) && !function_exists($parsers)){
            throw new InvalidArgumentException('Body parsers must be a callable');
        }

        $this->bodyParsers[$subtype] = $parsers;
    }
}