<?php
namespace Ant\Http;

/**
 * Class Request
 * @package Ant\Http
 * 可能改变实例的所有方法都必须保证请求实例不能被改变,使得它们保持当前消息的内部状态,并返回一个包含改变状态的实例.
 */
class Request extends Message implements \Psr\Http\Message\ServerRequestInterface{
    /**
     * @var string 请求资源
     */
    protected $requestTarget;
    /**
     * @var string http 请求方式
     */
    protected $method;
    /**
     * @var \Psr\Http\Message\UriInterface 实例
     */
    protected $uri;
    /**
     * @var array
     */
    protected $serverParams;
    /**
     * @var array cookie参数
     */
    protected $cookieParams;
    /**
     * @var array 查询参数
     */
    protected $queryParams;
    /**
     * @var array 每一个元素都为\Psr\Http\Message\UploadedFileInterface 实例
     */
    protected $uploadFiles;
    /**
     * @var array|object|null 主体参数
     */
    protected $bodyParsers;
    /**
     * @var array 属性
     */
    protected $attributes = [];

    public function initialize(){
        $server = $_SERVER;
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = '';
        $cookie = $_COOKIE;
        $files = '';
        $body = '';

        $headers = [];
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $key           = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$key] = explode(',', $value);
            }
        }

    }

    /**
     * @param array $headers                            http 头信息
     * @param string $method                            http 请求方式
     * @param \Psr\Http\Message\UriInterface $uri       uri
     * @param array $server                             服务器参数
     * @param array $cookie                             cookie参数
     * @param array $files                              上传文件
     * @param \Psr\Http\Message\StreamInterface $body   http请求内容
     */
    public function __construct(
        array $headers,
        $method,
        \Psr\Http\Message\UriInterface $uri,
        array $server,
        array $cookie,
        array $files = [],
        \Psr\Http\Message\StreamInterface $body
    ){

    }

    //获取请求目标(资源)
    public function getRequestTarget(){

    }

    //设置请求资源,需保持数据不变性
    public function withRequestTarget($requestTarget){

    }

    //获取请求方式
    public function getMethod(){

    }

    //设置请求方式,需保持数据不变性
    public function withMethod($method){

    }

    //获取uri,返回\Psr\Http\Message\UriInterface实例
    public function getUri(){

    }

    //设置uri,需要保持数据不变性
    public function withUri(\Psr\Http\Message\UriInterface $uri,$preserveHost = false){

    }

    //检索服务器参数
    public function getServerParams(){

    }

    //检索Cookie参数,返回Array
    public function getCookieParams(){

    }

    //设置cookie参数,保持数据不变
    public function withCookieParams(array $cookies){

    }

    //获取查询参数(也就是GET),返回Array
    public function getQueryParams(){
        //TODO::parse_str
    }

    //添加查询参数,保持数据不变性
    public function withQueryParams(array $query){

    }

    //获取上传文件信息,需返回数组
    public function getUploadedFiles(){

    }

    //添加上传文件信息
    public function withUploadedFiles(array $uploadedFiles){

    }

    //检索请求主体提供任何参数
    public function getParsedBody(){

    }

    //输入数据只能为null,数组,对象,保持数据不变性
    public function withParsedBody($data){

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
    public function withoutAttribute($name){

    }

}