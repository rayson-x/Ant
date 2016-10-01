<?php
namespace Ant\Router;

use RuntimeException;
use InvalidArgumentException;
use FastRoute\Dispatcher;
use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Ant\Interfaces\Router\RouterInterface;

class Router implements RouterInterface
{
    use Middleware,ParseGroupAttributes;
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
     * 异常处理函数
     *
     * @var false|callable
     */
    protected $exceptionHandle = false;

    /**
     * 请求的路由
     *
     * @var string
     */
    protected $routeRequest;

    /**
     * 路由
     *
     * @var RouteCollector
     */
    protected $routes;

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
     * 分组属性
     *
     * @var
     */
    protected $groupAttributes = null;

    public function __construct()
    {
        $this->container = Container::getInstance();
        $this->routes = $this->container->make(\FastRoute\RouteCollector::class);
    }

    public function group(array $attributes, \Closure $action)
    {
        //开启路由之后,再进行路由分组会直接生成路由映射
        if(!$this->routeStartEnable){
            $keyword = '##';

            //路由器开始时禁用关键词功能
            if(isset($attributes['keyword'])){
                $keyword = $attributes['keyword'];
            }

            $this->group[$keyword][] = [$attributes,$action];
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

    public function map($methods, $uri, $action)
    {
        $route = $this->newRoute($methods, $uri, $action);

        $this->routes->addRoute(
            $route->getMethod(),
            $route->getUri(),
            $route->getAction()
        );

        return $route;
    }

    public function newRoute($method, $uri, $action)
    {
        return new Route($method, $uri, $action,$this->groupAttributes);
    }

    public function setDispatcher(\FastRoute\Dispatcher $dispatcher)
    {

    }

    public function createDispatcher()
    {
        return new \FastRoute\Dispatcher\GroupCountBased($this->routes->getData());
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return array
     */
    public function dispatch(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->routeRequest = $request->getMethod().$request->getRequestRoute();

        $this->addRouteFromGroup($request->getRequestRoute());

        return $this->handleDispatcher($this->createDispatcher()->dispatch(
            $request->getMethod(),
            $request->getRequestRoute()
        ));
    }

    /**
     * 添加分组路由
     *
     * @param $pathInfo
     */
    protected function addRouteFromGroup($pathInfo)
    {
        //获取关键词
        $keywords = array_keys(array_reverse($this->group));

        //进行关键词匹配
        foreach($keywords as $keyword){
            if(preg_match($keyword,$pathInfo) === 1){
                $this->compileRoute($this->group[$keyword]);

                break;
            }
        }
    }

    /**
     * 将分组编译为符合规范的路由
     *
     * @param array $group
     */
    protected function compileRoute(array $group)
    {
        foreach($group as list($attributes,$action)){
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
     * 处理路由调度器返回数据
     *
     * @param $routeInfo
     * @return array
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
     * @param $action Route
     * @param array $args
     * @return array
     */
    protected function handleFoundRoute(Route $action,$args = [])
    {
        $handle = [];

        if($action->getMiddleware()){
            $handle = $this->createMiddleware($action->getMiddleware());
        }

        //添加为最后一节中间件
        $handle[] = function(...$params)use($action,$args){
            $this->callAction($action,array_merge($args,$params));
        };

        return $handle;
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

        return array_map(function($name){
            $middleware = isset($this->handlers[$name]) ? $this->handlers[$name] : $name;

            //获取可以回调的路由
            if(is_string($middleware)){
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

        if (is_string($callback) && ! strpos($callback, '@') === false) {
            $callback .= '@__invoke';
        }

        try{
            return $this->container->call($callback,$args);
        }catch (\BadMethodCallException $e){
            throw new \Ant\Http\Exception(404);
        }
    }

    public function run(\Ant\Http\Request $req,$res)
    {
        $this->routeStartEnable = true;

        $this->execute(
            $this->dispatch($req),
            [$req,$res]
        );
    }
}