<?php
namespace Ant\Http;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ServerRequest
 * @package Ant\Http
 * @see http://www.php-fig.org/psr/psr-7/
 */
class ServerRequest extends Message implements ServerRequestInterface
{
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
     * @var array
     */
    protected $bodyParsers = [];

    /**
     * 属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * 通过请求上下文环境创建
     *
     * @param Environment $env
     * @return static
     */
    public static function createFromRequestEnvironment(Environment $env)
    {
        return new static(
            Uri::createFromEnvironment($env),
            $env->createHeader(),
            $env->createCookie(),
            $env->toArray(),
            RequestBody::createFromCgi(),
            UploadedFile::parseUploadedFiles($_FILES)
        );
    }

    /**
     * Request constructor.
     *
     * @param UriInterface $uri
     * @param array $headers
     * @param array $cookies
     * @param array $serverParams
     * @param StreamInterface|null $body
     * @param array $uploadFiles
     */
    public function __construct(
        UriInterface $uri,
        array $headers = [],
        array $cookies = [],
        array $serverParams = [],
        StreamInterface $body = null,
        array $uploadFiles = []
    ){
        $this->uri = $uri;
        $this->requestTarget = $uri->getPath();
        $this->headers = $headers;
        $this->serverParams = $serverParams;
        $this->uploadFiles = $uploadFiles;
        $this->cookieParams = $cookies;
        $this->body = $body ?: new Body(fopen('php://temp','w+'));
    }

    /**
     * 获取请求目标(资源)
     *
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->requestTarget ?: '/';
    }

    /**
     * 设置请求资源
     *
     * @param mixed $requestTarget
     * @return Request
     */
    public function withRequestTarget($requestTarget)
    {
        return $this->changeAttribute('requestTarget',$requestTarget);
    }

    /**
     * 获取http请求方式,支持重写请求方式
     *
     * @return string
     */
    public function getMethod()
    {
        if($this->method){
            return $this->method;
        }

        $method = isset($this->serverParams['REQUEST_METHOD']) ? strtoupper($this->serverParams['REQUEST_METHOD']) : 'GET';

        // 尝试重写请求方法
        if ($method == 'POST') {
            $override = $this->post('_method') ?: $this->getHeaderLine('x-http-method-override');
            if($override){
                $method = strtoupper($override);
            }
        }

        return $this->method = $method;
    }

    /**
     * 设置请求方式
     *
     * @param string $method
     * @return Request
     */
    public function withMethod($method)
    {
        return $this->changeAttribute('method',$method);
    }

    /**
     * 获取URI
     *
     * @return UriInterface
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 设置uri
     *
     * 如果开启host保护
     * HOST为空,新的URI包含HOST,更新
     * HOST为空,新的URI不包含,不更新
     * HOST不为空,不更新
     *
     * @param UriInterface $uri
     * @param bool|false $preserveHost
     * @return Request
     */
    public function withUri(UriInterface $uri,$preserveHost = false)
    {
        if(!$preserveHost){
                $host = explode(',',$uri->getHost());
        }else{
            if( (!$this->hasHeader('host') || empty($this->getHeaderLine('host'))) && $uri->getHost() !== ''){
                $host = explode(',',$uri->getHost());
            }
        }

        if(empty($host)){
            $host = $this->getHeader('host');
        }

        return $this->changeAttribute(['headers','host'],$host);
    }

    /**
     * 获取server参数
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * @param $key
     * @return array|null
     */
    public function getServerParam($key = null)
    {
        if($key === null){
            return $this->serverParams;
        }

        return isset($this->serverParams[$key]) ? $this->serverParams[$key] : null;
    }

    /**
     * 获取cookie参数
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * 设置cookie参数
     *
     * @param array $cookies
     * @return Request
     */
    public function withCookieParams(array $cookies)
    {
        return $this->changeAttribute('cookieParams',$cookies);
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

    /**
     * 设置查询参数
     *
     * @param array $query
     * @return Request
     */
    public function withQueryParams(array $query)
    {
        return $this->changeAttribute('queryParams',$query);
    }

    /**
     * 向get中添加参数
     *
     * @param array $query
     * @return Request
     */
    public function withAddedQueryParams(array $query)
    {
        return $this->withQueryParams(array_merge($this->getQueryParams(),$query));
    }

    /**
     * 获取上传文件信息
     *
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->uploadFiles;
    }

    /**
     * 添加上传文件信息
     *
     * @param array $uploadedFiles
     * @return Request
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->changeAttribute('uploadFiles',$uploadedFiles);
    }

    /**
     * 获取body解析结果
     *
     * @return array|null|object
     */
    public function getParsedBody()
    {
        if(!empty($this->bodyParsed)){
            return $this->bodyParsed;
        }

        // "Content-Type" 为 "multipart/form-data" 时候 php://input 是无效的
        if($this->getServerParam('REQUEST_METHOD') === 'POST'
            && in_array($this->getContentType(),['multipart/form-data','application/x-www-form-urlencoded'])
        ){
            return $this->bodyParsed = $_POST;
        }

        if($this->body->getSize() === 0){
            return null;
        }

        list($type,$subtype) = explode('/',$this->getContentType(),2);

        if(in_array(strtolower($type),['application','text']) && isset($this->bodyParsers[$subtype])){
            //调用body解析函数
            $body = (string)$this->getBody();
            $parsed = call_user_func($this->bodyParsers[$subtype],$body);

            if (!(is_null($parsed) || is_object($parsed) || is_array($parsed))){
                throw new RuntimeException('Request body media type parser return value must be an array, an object, or null');
            }

            return $this->bodyParsed = $parsed;
        }

        return null;
    }

    /**
     * 设置body解析结果
     *
     * @param array|null|object $data
     * @return Request
     */
    public function withParsedBody($data)
    {
        if(!(is_null($data) || is_array($data) || is_object($data))){
            throw new InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        return $this->changeAttribute('bodyParsed',$data);
    }

    /**
     * 获取所有属性
     *
     * @return mixed[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * 获取一个属性的值
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name];
    }

    /**
     * 设置一个属性.
     *
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function withAttribute($name, $value)
    {
        return $this->changeAttribute(['attributes',$name],$value);
    }

    /**
     * 删除一个属性
     *
     * @see getAttributes()
     * @param string $name .
     * @return self
     */
    public function withoutAttribute($name)
    {
        $result = clone $this;
        if(array_key_exists($name,$result->attributes)){
            unset($result->attributes[$name]);
        }

        return $result;
    }

    /**
     * 检查请求方式
     *
     * @param $method
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === $method;
    }

    /**
     * 查看是否是GET请求
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * 查看是否是POST请求
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * 查看是否是PUT请求
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * 查看是否是DELETE请求
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * 检查是否是异步请求
     * 注意 : 主流JS框架发起AJAX都有此参数,如果是原生AJAX需要手动添加到http头
     *
     * @return bool
     */
    public function isAjax()
    {
        return strtolower($this->getHeaderLine('x-requested-with')) === 'xmlhttprequest';
    }

    /**
     * 获取GET参数
     *
     * @param null $key
     * @return array|null
     */
    public function get($key = null)
    {
        $get = $this->getQueryParams();
        if($key === null){
            return $get;
        }

        return isset($get[$key]) ? $get[$key] : null;
    }

    /**
     * 获取POST参数,仅在请求方式为POST时有效
     *
     * @param null $key
     * @return array|null|object
     */
    public function post($key = null)
    {
        if($this->serverParams['REQUEST_METHOD'] === 'POST'){
            return $this->getBodyParam($key);
        }

        return $key ? [] : null;
    }

    /**
     * 获取cookie参数
     *
     * @param null $key
     * @return array|null
     */
    public function cookie($key = null)
    {
        $cookie = $this->getCookieParams();
        if($key === null){
            return $cookie;
        }

        return isset($cookie[$key]) ? $cookie[$key] : null;
    }

    /**
     * 获取body参数
     *
     * @param null $key
     * @return array|null|object
     */
    public function getBodyParam($key = null)
    {
        if($key === null){
            return $this->getParsedBody();
        }

        $params = $this->getParsedBody();

        if(is_array($params) && array_key_exists($key,$params)){
            return $params[$key];
        }elseif (is_object($params) && property_exists($params, $key)){
            return $params->$key;
        }

        return null;
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

    /**
     * @return string
     */
    public function __toString()
    {
        $input = sprintf(
            '%s %s HTTP/%s',
            $this->getMethod(),
            $this->getRequestTarget(),
            $this->getProtocolVersion()
        );
        $input .= PHP_EOL;
        foreach($this->getHeaders() as $name => $value){
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $name = implode('-',array_map('ucfirst',explode('-',$name)));
            $input .= sprintf('%s: %s',$name,$value).PHP_EOL;
        }
        $input .= PHP_EOL;
        $input .= (string)$this->getBody();

        return $input;
    }
}