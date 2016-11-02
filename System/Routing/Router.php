<?php
namespace Ant\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use Ant\Middleware\Middleware;
use Ant\Exception\NotFoundException;
use Ant\Interfaces\Router\RouterInterface;
use Ant\Exception\MethodNotAllowedException;
use Ant\Interfaces\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

/**
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

        //合并父级分组的中间件与响应类型
        $attributes = $this->mergeMiddlewareGroup(
            $this->mergeResponseType($attributes)
        );

        //获取父级分组命名空间
        if(isset($this->groupAttributes['namespace'])){
            $attributes['namespace'] = rtrim($this->groupAttributes['namespace'],'\\').'\\'.trim($attributes['namespace'],'\\');
        }

        return $attributes;
    }

    /**
     * 注册一个“GET”路由
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
     * 注册一个“POST”路由
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
     * 注册一个“PUT”路由
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
     * 注册一个“DELETE”路由
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
     * 注册一个“HEAD”路由
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
     * 注册一个“PATCH”路由
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
     * 注册一个“OPTIONS”路由
     *
     * @param $uri
     * @param $action
     * @return Route
     */
    public function options($uri,$action)
    {
        return $this->map('OPTIONS',$uri,$action);
    }

    /**
     * 注册一个接受7种请求方式的路由
     *
     * @param $uri
     * @param $action
     * @return Route
     */
    public function any($uri,$action)
    {
        return $this->map(['GET','POST','PUT','DELETE','HEAD','PATCH','OPTIONS'],$uri,$action);
    }

    /**
     * 注册一个路由映射
     *
     * @param $methods
     * @param $uri
     * @param $action
     * @return Route
     */
    public function map($methods, $uri, $action)
    {
        $route = $this->newRoute($methods, $uri, $action);

        foreach((array) $methods as $method){
            $this->routes[$method.$route->getUri()] = $route;
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
     * @param \Psr\Http\Message\ServerRequestInterface $req
     * @param \Psr\Http\Message\ResponseInterface $res
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch(PsrRequest $req, PsrResponse $res)
    {
        // 获取请求的方法,路由,跟返回类型
        list($method,$pathInfo,$type) = $this->parseIncomingRequest($req);

        // 匹配路由
        if(isset($this->routes[$method.$pathInfo])){
            $route = $this->handleFoundRoute(
                $this->routes[$method.$pathInfo]
            );
        }else{
            $route = $this->handleDispatcher(
                $this->createDispatcher()->dispatch($method,$pathInfo)
            );
        }

        // 请求的类型是否能够响应
        if(!in_array($type,$route->getResponseType())){
            throw new \RuntimeException("Requested [$type] format cannot be returned");
        }

        // 调用中间件
        $result = (new Middleware)
            ->send($req,$res)
            ->through($this->routeMiddleware)
            ->then($this->callRoute($route));

        // 渲染响应结果
        if(!$result instanceof PsrResponse && !is_null($result)){
            $result = Decorator::selectRenderer($type)
                ->setWrapped($result)
                ->renderResponse($res);
        }

        return $result;
    }

    /**
      * 解析请求
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return array
     */
    protected function parseIncomingRequest(PsrRequest $request)
    {
        $serverParams = $request->getServerParams();
        $requestRoute = [ $request->getMethod() ];

        return array_merge($requestRoute,$this->getRoute(
            parse_url($serverParams['REQUEST_URI'], PHP_URL_PATH),
            parse_url($serverParams['SCRIPT_NAME'], PHP_URL_PATH)
        ));
    }

    /**
     * 获取路由
     *
     * @param string $requestScriptName
     * @param string $requestUri
     * @return array
     */
    protected function getRoute($requestUri,$requestScriptName)
    {
        $requestScriptDir = dirname($requestScriptName);

        //获取基础路径
        if (stripos($requestUri, $requestScriptName) === 0) {
            $basePath = $requestScriptName;
        } elseif ($requestScriptDir !== '/' && stripos($requestUri, $requestScriptDir) === 0) {
            $basePath = $requestScriptDir;
        }

        if (isset($basePath)) {
            //获取请求的路径
            $requestUri = '/'.trim(substr($requestUri, strlen($basePath)), '/');
        }

        $type = $this->parseAcceptType($requestUri);

        return [$requestUri,$type];
    }

    /**
     * 解析客户端请求的数据格式
     *
     * @param $requestUri
     * @return string
     */
    protected function parseAcceptType(& $requestUri)
    {
        if(false !== ($pos = strrpos($requestUri,'.'))){
            $type = substr($requestUri, $pos + 1);
            $requestUri = strstr($requestUri, '.', true);
        }

        return isset($type) ? $type : 'html';
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
                $r->addRoute($route->getMethod(), $route->getUri(), $route);
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
                throw new NotFoundException;

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
     * @return Route
     */
    protected function handleFoundRoute(Route $action,$args = [])
    {
        if($handle = $action->getMiddleware()){
            $this->routeMiddleware = $this->createMiddleware($action->getMiddleware());
        }

        $action->setArguments($args);

        return $action;
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

        //获取可以回调的路由
        return array_map(function($middleware){
            $middleware = $this->getMiddleware($middleware);
            if(is_string($middleware)){
                return $this->container->make($middleware);
            }elseif($middleware instanceof \Closure){
                return $middleware;
            }

            throw new InvalidArgumentException("Middleware [$middleware] does not exist");
        },$middleware);
    }

    /**
     * 获取中间件
     *
     * @param $middleware
     * @return string
     */
    protected function getMiddleware($middleware)
    {
        if(is_string($middleware)){
            $middleware = isset($this->middleware[$middleware]) ? $this->middleware[$middleware] : $middleware;
        }

        return $middleware;
    }

    /**
     * 调用基于数组的路由
     *
     * @param $action Route
     * @return \Closure
     */
    protected function callRoute(Route $action)
    {
        return function()use($action){
            $callback = $action->getAction();
            $action->setArguments(func_get_args());

            if (is_string($callback) && strpos($callback, '@') === false) {
                $callback .= '@__invoke';
            }

            try{
                return $this->container->call($callback,$action->getArguments());
            }catch (\BadMethodCallException $e){
                throw new NotFoundException;
            }
        };
    }
}