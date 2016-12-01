<?php
namespace Ant\Http;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Todo::重构ServerRequest
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
     * 通过请求上下文环境创建
     *
     * @param Environment $env
     * @return static
     */
    public static function createFromRequestEnvironment(Environment $env)
    {
        return new static(
            $env->createServerParams(),
            $env->createHeaderParams(),
            $env->createCookieParams(),
            $_GET,
            $_POST,
            $_FILES,
            RequestBody::createFromCgi()
        );
//        return new static(
//            Uri::createFromEnvironment($env),
//            $env->createHeader(),
//            $env->createCookie(),
//            $env->toArray(),
//            RequestBody::createFromCgi(),
//            UploadedFile::parseUploadedFiles($_FILES)
//        );
    }

    /**
     * ServerRequest constructor.
     * @param array $serverParams           $_SERVER 参数
     * @param array $headers                从$_SERVER中解析出来的参数
     * @param array $cookies                $_COOKIE参数
     * @param array $queryParams            $_GET参数
     * @param array $bodyParams             $_POST存在时使用的参数
     * @param array $uploadFiles            $_FILES参数
     * @param StreamInterface|null $body    php://input流
     */
    public function __construct(
        array $serverParams = [],
        array $headers = [],
        array $cookies = [],
        array $queryParams = [],
        array $bodyParams = [],
        array $uploadFiles = [],
        StreamInterface $body = null
    ){
        $this->serverParams = $serverParams;
        $this->headers = $headers;
        $this->cookieParams = $cookies;
        $this->queryParams = $queryParams;
        $this->bodyParams = $bodyParams;
        $this->uploadFiles = UploadedFile::parseUploadedFiles($uploadFiles);
        $this->body = $body ?: new Body();
        $this->uri = Uri::createFromEnvironment($serverParams);
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