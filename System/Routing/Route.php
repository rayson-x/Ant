<?php
namespace Ant\Routing;

use BadFunctionCallException;
use Ant\Interfaces\Router\RouteInterface;

/**
 * Class Route
 * @package Ant\Routing
 */
class Route implements RouteInterface
{
    use ParseGroupAttributes;
    /**
     * Http 请求
     *
     * @var array
     */
    protected $method;

    /**
     * 请求资源
     *
     * @var string
     */
    protected $uri;

    /**
     * 回调的函数
     *
     * @var callable|string
     */
    protected $callback;

    /**
     * 路由需要的中间件
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * 参数
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * 响应类型
     *
     * @var array
     */
    protected $responseType = [];

    /**
     * Route constructor.
     * @param $method
     * @param $uri
     * @param $action
     * @param array $groupAttributes
     */
    public function __construct($method,$uri,$action,array $groupAttributes = [])
    {
        $action = $this->parseAction($action);
        $this->groupAttributes = $groupAttributes;

        if(isset($groupAttributes)){
            //继承路由组信息
            $uri = $this->mergeGroupPrefixAndSuffix($uri);

            $action = $this->mergeGroupNamespace(
                $this->mergeMiddlewareGroup(
                    $this->mergeResponseType($action)
                )
            );
        }

        $this->parseUses($action);

        $this->method = $method;
        $this->uri = '/'.trim($uri,'/');
        $this->callback = $action['uses'];
        $this->middleware = isset($action['middleware']) ? $action['middleware'] : [];
        $this->responseType = isset($action['type']) ? $action['type'] : ['html'];
    }

    /**
     * 获取路由映射的方法
     *
     * @param $action
     * @return \Closure
     */
    protected function parseUses(& $action)
    {
        if(empty($action['uses'])){
            foreach($action as $value){
                if($value instanceof \Closure){
                    return $action['uses'] = $value;
                }
            }

            throw new BadFunctionCallException('Routing callback failed');
        }
    }

    /**
     * 解析行为
     *
     * @param $action
     * @return array
     */
    protected function parseAction($action)
    {
        if(is_string($action)){
            return ['uses' => $action];
        }elseif(!is_array($action)){
            return [$action];
        }

        if(isset($action['middleware']) && is_string($action['middleware'])){
            $action['middleware'] = explode('|',$action['middleware']);
        }

        return $action;
    }

    /**
     * 获取请求方式
     *
     * @return array
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 获取路由Uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 获取行为
     *
     * @return $this
     */
    public function getAction()
    {
        return $this->callback;
    }

    /**
     * 获取路由需要的中间件
     *
     * @return mixed
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * 替换现有中间件
     *
     * @param $middleware
     * @return self $this
     */
    public function replaceMiddleware($middleware)
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * 添加一个中间件
     *
     * @param $middleware
     * @return self $this
     */
    public function addMiddleware($middleware)
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * 重置中间件
     *
     * @return self $this;
     */
    public function resetMiddleware()
    {
        $this->middleware = [];

        return $this;
    }

    /**
     * 获取一个默认路由参数
     *
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function getArgument($name, $default = null)
    {
        return array_key_exists($name,$this->arguments)
            ? $this->arguments[$name]
            : $default;
    }

    /**
     * 获取所有默认路由参数
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * 设置一个默认的路由参数
     *
     * @param $name
     * @param null $default
     * @return static $this
     */
    public function setArgument($name, $default = null)
    {
        $this->arguments[$name] = $default;

        return $this;
    }

    /**
     * 设置一组默认的路由参数
     *
     * @param array $arguments
     * @return $this;
     */
    public function setArguments(array $arguments)
    {
        foreach($arguments as $name => $value){
            $this->arguments[$name] = $value;
        }
    }

    /**
     * @return array
     */
    public function getResponseType()
    {
        return $this->responseType;
    }

    /**
     * @param array $responseType
     */
    public function setResponseType(array $responseType)
    {
        $this->responseType = $responseType;
    }

    /**
     * @param $type
     */
    public function withAddResponseType($type)
    {
        array_push($this->responseType,$type);
    }
}