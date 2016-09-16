<?php
namespace Ant\Http;

use Ant\Collection;
use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Request
 * @package Ant\Http
 * @see http://www.php-fig.org/psr/psr-7/
 * 可能改变实例的所有方法都必须保证请求实例不能被改变,使得它们保持当前消息的内部状态,并返回一个包含改变状态的实例.
 */
class Request extends Message implements ServerRequestInterface{
    /**
     * 是否保持数据不变性
     *
     * @var bool
     */
    protected $immutability = true;

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
     * 属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * 支持的http请求方式
     *
     * @var array
     */
    protected $viewMethods = [
        'GET' => 1,
        'PUT' => 1,
        'POST' => 1,
        'DELETE' => 1,
        'HEAD' => 1,
        'PATCH' => 1,
    ];

    //TODO::调整构造函数,将构造职责与通过环境构造进行分离
    /**
     * @param Environment $server
     */
    public function __construct(Environment $server)
    {
        $this->uri = Uri::createFromEnvironment($server);
        $this->serverParams = $server->all();
        $this->headers = Header::createFromEnvironment($server);
        $this->cookieParams = $_COOKIE;
        $this->body = new RequestBody();
        $this->attributes = new Collection();

        $type = ['application/x-www-form-urlencoded','multipart/form-data'];
        if($server['REQUEST_METHOD'] === 'POST' && in_array($this->getContentType(),$type)){
            $this->bodyParsed = $_POST;
        }
    }

    /**
     * 获取请求目标(资源)
     *
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->serverParams['REQUEST_URI'] ?:'/';
    }

    /**
     * 设置请求资源
     *
     * @param mixed $requestTarget
     * @return Request
     */
    public function withRequestTarget($requestTarget)
    {
        return $this->immutability($this,['serverParams','REQUEST_URI'],$requestTarget);
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
        if ($method !== 'POST') {
            return $this->method = $method;
        }

        $override = $this->getHeaderLine('x-http-method-override') ?: $this->post('_method');
        if($override){
            $method = $this->filterMethod($override);
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
        return $this->immutability($this,'method',$this->filterMethod($method));
    }

    /**
     * 过滤非法请求方式
     *
     * @param $method
     * @return string
     */
    public function filterMethod($method)
    {
        $method = strtoupper($method);

        if(!array_key_exists($method,$this->viewMethods)){
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }

        return $method;
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

        return $this->immutability($this,['headers','host'],$host);
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
        return $this->immutability($this,'cookieParams',$cookies);
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
        return $this->immutability($this,'queryParams',$query);
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
     * @return array[]|UploadedFileInterface
     */
    public function getUploadedFiles()
    {
        if(!$this->uploadFiles){
            $this->uploadFiles = UploadedFile::parseUploadedFiles($_FILES);
        }
        return $this->uploadFiles;
    }

    //添加上传文件信息
    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->immutability($this,'uploadFiles',$uploadedFiles);
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

        if(!$this->body){
            return null;
        }

        $contentType = $this->getContentType();
        $parts = explode('/',$contentType);
        $type = array_shift($parts);
        $subtype = array_pop($parts);

        if(in_array(strtolower($type),['application','text']) && isset($this->bodyParsers[$subtype])){
            //调用body解析函数
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

        return $this->immutability($this,'bodyParsed',$data);
    }

    /**
     * 获取所有属性
     *
     * @return mixed[]
     */
    public function getAttributes()
    {
        return $this->attributes->all();
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
        return $this->attributes->get($name,$default);
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
        $result = clone $this;
        $result->attributes->set($name, $value);
        return $result;
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
        $result->attributes->remove($name);
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
     * 获取POST参数
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
     * 获取body参数
     *
     * @param null $key
     * @return array|null|object
     */
    public function getBodyParam($key = null)
    {
        $params = $this->getParsedBody();
        if($key === null){
            return $params;
        }

        if(is_array($params) && isset($params[$key])){
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
     * 保持数据不变性
     *
     * @param $instance Request
     * @param $attribute string
     * @param $value mixed
     * @return Request
     */
    public function immutability(Request $instance,$attribute,$value)
    {
        $result = clone $instance;
        if(is_array($attribute)){
            $key = array_pop($attribute);
            $array = array_shift($attribute);

            $result->$array[$key] = $value;
        }else{
            $result->$attribute = $value;
        }

        return $result;
    }
}