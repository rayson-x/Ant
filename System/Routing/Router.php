<?php
namespace Ant\Routing;

use RuntimeException;
use FastRoute\Dispatcher;
use InvalidArgumentException;
use FastRoute\RouteCollector;
use Ant\Middleware\Middleware;
use Ant\Exception\NotFoundException;
use Ant\Exception\MethodNotAllowedException;
use Ant\Interfaces\Router\RouterInterface;
use Ant\Interfaces\Container\ContainerInterface;

/**
 * TODO::“关键词”功能
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
     * @var ContainerInterface
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
     * @var array[Route]
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
     * 路由器中间件
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
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            $this->compileRoute([$attributes,$action]);
        }
    }

    /**
     * 注册一个“GET”请求的路由
     *
     * @param $uri
     * @param $action
     * @return Route
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
     * @return Route
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
     * @return Route
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
     * @return Route
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
     * @return Route
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
     * @return Route
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
     * @return Route
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

        foreach($this->group as $group){
            $this->compileRoute($group);
        }

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
     * 解析请求
     *
     * @param $request
     * @return array
     */
    protected function parseIncomingRequest($request)
    {
        if($request instanceof \Ant\Http\Request){
            return [$request->getMethod(),$request->getRequestRoute()];
        }elseif($request instanceof \Psr\Http\Message\ServerRequestInterface){

        }else{

        }
    }

    /**
     * 注册路由分组中的路由
     *
     * @param $routeGroup array
     */
    protected function compileRoute(array $routeGroup)
    {
        list($attributes,$action) = $routeGroup;
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

        $action($this);

        $this->groupAttributes = $parentGroupAttributes;
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
        return $this->dispatcher ?: \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route->getMethod(), $route->getUri(), $route->getAction());
            }
        });
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
                // 抛出404异常
                throw new NotFoundException();

            case Dispatcher::METHOD_NOT_ALLOWED:
                // 抛出405异常,同时响应客户端资源支持的所有 HTTP 方法
                throw new MethodNotAllowedException($routeInfo[1]);

            case Dispatcher::FOUND:
                // 匹配成功
                return $this->handleFoundRoute($routeInfo[1],$routeInfo[2]);
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
     * 添加可选中间件
     *
     * @param $middleware
     */
    public function addMiddleware($middleware)
    {
        if(!is_array($middleware)){
            $middleware = func_get_args();
        }

        $this->middleware = array_merge($this->middleware,$middleware);
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
                $middleware = isset($this->middleware[$middleware]) ? $this->middleware[$middleware] : $middleware;
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
     * @return mixed|\Ant\Http\Response
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
            throw new NotFoundException();
        }
    }

    /**
     * 启动路由器
     *
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