<?php
namespace Ant\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ServerRequest
 * @package Ant\Http
 * @see http://www.php-fig.org/psr/psr-7/
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * 服务器和执行环境信息
     *
     * @var array
     */
    protected $serverParams;

    /**
     * 属性
     *
     * @var array
     */
    protected $attributes = [];

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
     * ServerRequest constructor.
     * @param array $serverParams
     * @param array $cookieParams
     * @param array $queryParams
     * @param array $bodyParams
     * @param array $uploadFiles
     * @param StreamInterface|null $body
     */
    public function __construct(
        array $serverParams = null,
        array $cookieParams = null,
        array $queryParams = null,
        array $bodyParams = null,
        array $uploadFiles = null,
        StreamInterface $body = null
    ){
        $this->serverParams = $serverParams ?: $_SERVER;
        $this->cookieParams = $cookieParams ?: $_COOKIE;
        $this->queryParams = $queryParams ?: $_GET;
        $this->bodyParams = $bodyParams ?: $_POST;
        $this->uploadFiles = UploadedFile::parseUploadedFiles($uploadFiles ?: $_FILES);
        $this->body = $body ?: RequestBody::createFromCgi();

        $this->initialize();
    }

    /**
     * 初始化参数
     */
    protected function initialize()
    {
        foreach ($this->serverParams as $key => $value) {
            //提取HTTP头
            if (isset($this->special[$key]) || strpos($key, 'HTTP_') === 0) {
                $key = strtolower(str_replace('_', '-', $key));
                $key = (strpos($key, 'http-') === 0) ? substr($key, 5) : $key;
                $this->headers[$key] = explode(',', $value);
            }
        }

        $this->requestTarget = isset($this->serverParams['REQUEST_URI'])
            ? $this->serverParams['REQUEST_URI']
            : '/';

        $this->uri = Uri::createFromEnvironment($this->serverParams);
    }

    /**
     * 获取Http动词
     *
     * @return string
     */
    public function getMethod()
    {
        if ($this->method === null) {
            $this->method = isset($this->serverParams['REQUEST_METHOD']) ? $this->serverParams['REQUEST_METHOD'] : 'GET';

            if ($customMethod = $this->getHeaderLine('X-Http-Method-Override')) {
                $this->method = $customMethod;
            } elseif ($this->method === 'POST') {
                $this->method = $this->getBodyParam('_method');
            }
        }

        return $this->method;
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
     * @return string
     */
    protected function getScriptName()
    {
        return $this->getServerParam('SCRIPT_NAME') ?: parent::getScriptName();
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
}