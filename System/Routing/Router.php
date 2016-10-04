<?php
namespace Ant\Routing;

use RuntimeException;
use InvalidArgumentException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Ant\Interfaces\Router\RouterInterface;

/**
 * TODO::待重构“关键词”功能
 *
 * Class Routing
 * @package Ant\Routing
 */
class Router implements RouterInterface
{
    use ParseGroupAttributes;
    /**
     * 服务容器
     *
     * @var Container
     */
    protected $container;

    /**
     * 路由启动开关
     *
     * @var bool
     */
    protected $routeStartEnable = false;

    /**
     * 请求的路由
     *
     * @var string
     */
    protected $routeRequest;

    /**
     * 路由
     *
     * @var array
     */
    protected $routes = [];

    /**
     * 快速匹配路由
     *
     * @var array
     */
    protected $fastRoute = [];

    /**
     * 调度器
     *
     * @var false | \FastRoute\Dispatcher\GroupCountBased
     */
    protected $dispatcher = false;

    /**
     * 路由分组
     *
     * @var array
     */
    protected $group = [];

    /**
     * 装载的中间件
     *
     * @var array
     */
    protected $middleware = [];

    /**
     *
     *
     * @var array
     */
    protected $routeMiddleware = [];

    /**
     * 分组属性
     *
     * @var
     */
    protected $groupAttributes = [];

    /**
     * @var bool
     */
    protected $cacheFile = false;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /**
     * @param array $attributes
     * @param \Closure $action
     */
    public function group(array $attributes, \Closure $action)
    {
        //开启路由之后,再进行路由分组会直接生成路由映射
        if(!$this->routeStartEnable){
            $this->group[] = [$attributes,$action];
        }else{
            $this->compileRoute([
                [$attributes,$action]
            ]);
        }
    }

    /**
     * 注册一个“GET”请求的路由
     *
     * @param $uri
     * @param $action
     * @return $this
     */
    public function get($uri,$action)
    {
        return $this->map('GET',$uri,$action);
    }

    /**
     * 注册一个“POST”请求的路由
     *
     * @param $uri
     * @param $action
     * @return $this
     */
    public function post($uri,$action)
    {
        return $this->map('POST',$uri,$action);
    }

    /**
     * 注册一个“PUT”请求的路由
     *
     * @param $uri
     * @param $action
     * @return $this
     */
    public function put($uri,$action)
    {
        return $this->map('PUT',$uri,$action);
    }

    /**
     * 注册一个“DELETE”请求的路由
     *
     * @param $uri
     * @param $action
     * @return $this
     */
    public function delete($uri,$action)
    {
        return $this->map('DELETE',$uri,$action);
    }

    /**
     * 注册一个“HEAD”请求的路由
     *
     * @param $uri
     * @param $action
     * @return $this
     */
    public function head($uri,$action)
    {
        return $this->map('HEAD',$uri,$action);
    }

    /**
     * 注册一个“PATCH”请求的路由
     *
     * @param $uri
     * @param $action
     * @return $this
     */
    public function patch($uri,$action)
    {
        return $this->map('PATCH',$uri,$action);
    }

    /**
     * 注册一个接收请求的路由
     *
     * @param $uri
     * @param $action
     * @return $this
     */
    public function any($uri,$action)
    {
        return $this->map(['GET','POST','PUT','DELETE','HEAD','PATCH'],$uri,$action);
    }

    /**
     * @param $methods
     * @param $uri
     * @param $action
     * @return Route
     */
    public function map($methods, $uri, $action)
    {
        $route = $this->newRoute($methods, $uri, $action);

        $this->routes[] = $route;

        foreach((array) $methods as $method){
            $this->fastRoute[$method.$route->getUri()] = $route;
        }

        return $route;
    }

    /**
     * @param $methods
     * @param $uri
     * @param $action
     * @return Route
     */
    public function newRoute($methods, $uri, $action)
    {
        return new Route($methods, $uri, $action, $this->groupAttributes);
    }

    /**
     * @param null $request
     * @return \Closure
     */
    public function dispatch($request = null)
    {
        list($method,$pathInfo) = $this->parseIncomingRequest($request);

        $this->compileRoute($this->group);

        if(isset($this->fastRoute[$method.$pathInfo])){
            return $this->handleFoundRoute(
                $this->fastRoute[$method.$pathInfo]
            );
        }

        return $this->handleDispatcher(
            $this->createDispatcher()->dispatch($method,$pathInfo)
        );
    }

    /**
     * @param $request
     * @return array
     */
    protected function parseIncomingRequest($request)
    {
        if($request instanceof \Ant\Http\Request){
            return [$request->getMethod(),$request->getRequestRoute()];
        }else{
            //TODO::通过自己的处理方式获取请求信息
        }
    }

    /**
     * 将分组编译为符合规范的路由
     *
     * @param $routeGroup array
     */
    protected function compileRoute(array $routeGroup)
    {
        foreach($routeGroup as list($attributes,$action)){
            //保留父级分组属性
            $parentGroupAttributes = $this->groupAttributes;

            if (isset($attributes['middleware']) && is_string($attributes['middleware'])) {
                $attributes['middleware'] = explode('|', $attributes['middleware']);
            }

            //是否继承父级分组属性
            if(isset($attributes['extend']) && $attributes['extend'] === false){
                $this->groupAttributes = $attributes;
            }else{
                $this->groupAttributes = $this->extendParentGroupAttributes($attributes);
            }

            call_user_func($action, $this);

            $this->groupAttributes = $parentGroupAttributes;
        }
    }

    /**
     * 继承父级分组属性
     *
     * @param array $attributes
     * @return array
     */
    protected function extendParentGroupAttributes(array $attributes)
    {
        if($this->groupAttributes === null){
            return $attributes;
        }

        $attributes = array_merge([
            'prefix'    => '',
            'suffix'    => '',
            'namespace' => '',
            'middleware'=> [],
        ],$attributes);

        //获取父级分组uri前缀与后缀
        $fix = $this->mergeGroupPrefixAndSuffix(implode('-',[$attributes['prefix'],$attributes['suffix'],]));

        list($attributes['prefix'],$attributes['suffix']) = explode('-',$fix);

        //获取父级分组中间件
        $attributes = $this->mergeMiddlewareGroup($attributes);

        //获取父级分组命名空间
        if(isset($this->groupAttributes['namespace'])){
            $attributes['namespace'] = rtrim($this->groupAttributes['namespace'],'\\').'\\'.trim($attributes['namespace'],'\\');
        }

        return $attributes;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return \FastRoute\Dispatcher\GroupCountBased
     */
    protected function createDispatcher()
    {
        if (!$this->dispatcher) {
            $routeDefinitionCallback = function (RouteCollector $r) {
                foreach ($this->routes as $route) {
                    $r->addRoute($route->getMethod(), $route->getUri(), $route->getAction());
                }
            };

            if ($this->cacheFile) {
                $this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback,[
                    'cacheDisabled' => true,
                    'cacheFile' => $this->cacheFile,
                ]);
            } else {
                $this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);
            }
        }

        return $this->dispatcher;
    }

    /**
     * 处理路由调度器返回数据
     *
     * @param $routeInfo
     * @return \Closure
     */
    protected function handleDispatcher($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new \Ant\Http\Exception(404);

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new \Ant\Http\Exception(405);

            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo[1],$routeInfo[2]);

            default:
                throw new RuntimeException('The dispatcher returns the invalid parameter');
        }
    }

    /**
     * 处理映射成功的路由
     *
     * @param Route $action
     * @param array $args
     * @return \Closure
     */
    protected function handleFoundRoute(Route $action,$args = [])
    {
        if($handle = $action->getMiddleware()){
            $this->routeMiddleware = $this->createMiddleware($action->getMiddleware());
        }

        return function(...$params)use($action,$args){
            return $this->callAction($action,array_merge($args,$params));
        };
    }

    /**
     * 加载路由使用的中间件
     *
     * @param $middleware
     * @return array
     */
    protected function createMiddleware($middleware)
    {
        $middleware = is_string($middleware) ? explode('|', $middleware) : (array) $middleware;

        return array_map(function($middleware){
            //获取可以回调的路由
            if(is_string($middleware)){
                $middleware = isset($this->$middleware[$middleware]) ? $this->$middleware[$middleware] : $middleware;

                return $this->container->make($middleware);
            }elseif($middleware instanceof \Closure){
                return $middleware;
            }

            throw new InvalidArgumentException("Middleware [$middleware] does not exist");
        },$middleware);
    }

    /**
     * 调用基于数组的路由
     *
     * @param $action Route
     * @param array $args
     * @return mixed
     */
    protected function callAction(Route $action,$args = [])
    {
        $callback = $action->getCallable();
        $args = array_merge($action->getArguments(),$args);

        if (is_string($callback) && strpos($callback, '@') === false) {
            $callback .= '@__invoke';
        }

        try{
            return $this->container->call($callback,$args);
        }catch (\BadMethodCallException $e){
            throw new \Ant\Http\Exception(404);
        }
    }

    /**
     * @param $req
     * @param $res
     * @return mixed
     */
    public function run($req,$res)
    {
        try{
            //启动路由器
            $this->routeStartEnable = true;
            
            $routeCallback = $this->dispatch($req);

            return (new Middleware)
                ->send($req,$res)
                ->through($this->routeMiddleware)
                ->then($routeCallback);
        }finally{
            $this->routeStartEnable = false;
        }
    }
}