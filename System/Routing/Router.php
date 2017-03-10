<?php
namespace Ant\Routing;

use FastRoute\Dispatcher;
use Ant\Middleware\Pipeline;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ant\Http\Exception\NotFoundException;
use Ant\Routing\Interfaces\RouterInterface;
use Ant\Container\Interfaces\ContainerInterface;
use Ant\Http\Exception\MethodNotAllowedException;

/**
 * Todo::创建资源
 * Todo::绑定控制器?
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
     * 基础路径
     *
     * @var null|string
     */
    protected $routePath = null;

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
     * 支持的http请求方式,如果不在下列方法内会抛出异常
     *
     * @var array
     */
    protected $methods = [
        'GET',             // 请求资源
        'PUT',             // 创建资源
        'POST',            // 创建或者完整的更新了资源
        'DELETE',          // 删除资源
        'HEAD',            // 只获取某个资源的头部信息
        'PATCH',           // 局部更新资源
        'OPTIONS'          // 获取资源支持的HTTP方法
    ];

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
        if (isset($attributes['extend']) && $attributes['extend'] === false) {
            $this->groupAttributes = $attributes;
        } else {
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
        if ($this->groupAttributes === null) {
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

        //获取父级分组命名空间
        if (isset($this->groupAttributes['namespace'])) {
            $attributes['namespace'] = rtrim($this->groupAttributes['namespace'],'\\').'\\'.trim($attributes['namespace'],'\\');
        }

        return $attributes;
    }

    /**
     * @param $method
     * @param $args
     * @return Route
     */
    public function __call($method, $args)
    {
        if (count($args) < 2) {
            throw new \RuntimeException;
        }

        if (in_array($method, $this->methods)) {
            throw new \RuntimeException;
        }

        list($uri, $action) = $args;

        return $this->map(strtoupper($method), $uri, $action);
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
        $route = $this->createRoute($methods, $uri, $action);

        $this->routes[] = $route;

        foreach ((array) $methods as $method) {
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
    protected function createRoute($methods, $uri, $action)
    {
        return new Route($methods, $uri, $action, $this->groupAttributes);
    }

    /**
     * 设置路由基础路径
     *
     * @param $basePath
     */
    public function setRoutePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new \RuntimeException;
        }

        $this->routePath = $basePath;
    }

    /**
     * @param RequestInterface $req
     * @param ResponseInterface $res
     * @return mixed|ResponseInterface
     */
    public function dispatch(RequestInterface $req, ResponseInterface $res)
    {
        // Todo::选择性加载,针对Cgi模式,Cli模式下默认全部加载
        // Todo::处理Options请求

        // 获取请求的方法,路由,跟返回类型
        list($method, $routePath) = $this->parseIncomingRequest($req);

        // 过滤非法Http动词
        $this->filterMethod($method);
        // 匹配路由
        $route = $this->matching($method, $routePath);

        return (new Pipeline)
            ->send($req, $res)
            ->through($this->createMiddleware($route))
            ->then($this->callRoute($route));
    }

    /**
     * 解析请求,返回客户端请求的方法,资源,以及资源的返回方式
     *
     * @param RequestInterface $request
     * @return array
     */
    protected function parseIncomingRequest(RequestInterface $request)
    {
        $method = $request->getMethod();

        $routePath = $this->routePath ?: $request->getUri()->getPath();

        return [$method, $routePath];
    }

    /**
     * 过滤非法请求方式
     *
     * @param $method
     */
    protected function filterMethod($method)
    {
        if (!in_array($method,$this->methods)) {
            throw new MethodNotAllowedException($this->methods, sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }
    }

    /**
     * 匹配路由
     *
     * @param string $method
     * @param string $routePath
     * @return Route
     */
    protected function matching($method, $routePath)
    {
        if (isset($this->fastRoute[$method.$routePath])) {
            return $this->handleFoundRoute(
                $this->fastRoute[$method.$routePath]
            );
        }

        return $this->handleDispatcher(
            $this->createDispatcher()->dispatch($method, $routePath)
        );
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
    protected function handleFoundRoute(Route $action, $args = [])
    {
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
        if (!is_array($middleware)) {
            $middleware = func_get_args();
        }

        $this->middleware = array_merge($this->middleware,$middleware);
    }

    /**
     * 加载路由使用的中间件
     *
     * @param Route $route
     * @return mixed
     */
    protected function createMiddleware(Route $route)
    {
        $middleware = $route->getMiddleware();

        //获取可以回调的路由
        return array_map(function($middleware) {
            if (is_string($middleware)) {
                $middleware = isset($this->middleware[$middleware])
                    ? $this->middleware[$middleware]
                    : $middleware;

                return $this->container->make($middleware);
            } elseif ($middleware instanceof \Closure) {
                return $middleware;
            }

            throw new InvalidArgumentException("Middleware [$middleware] does not exist");
        },$middleware);
    }

    /**
     * 调用基于数组的路由
     *
     * @param $action Route
     * @return \Closure
     */
    protected function callRoute(Route $action)
    {
        return function () use ($action) {
            $callback = $action->getAction();

            if (is_string($callback) && strpos($callback, '@') === false) {
                $callback .= '@__invoke';
            }

            try {
                return $this->container->call($callback,$action->getArguments());
            } catch (\BadMethodCallException $e) {
                throw new NotFoundException;
            }
        };
    }
}