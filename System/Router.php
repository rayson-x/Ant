<?php
namespace Ant;

use Closure;
use Exception;
use Throwable;
use RuntimeException;
use BadFunctionCallException;
use InvalidArgumentException;
use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher\GroupCountBased;

//TODO::RESTful风格路由
//TODO::路由缓存
//TODO::通过反射生成路由缓存 需要Console支持
class Router
{
    use Middleware{
        Middleware::execute as executeMiddleware;
    }

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
     * @var array
     */
    protected $routes = [];

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
    protected $group = [
        '/' =>  [], //默认关键词
    ];

    /**
     * 分组属性
     *
     * @var
     */
    protected $groupAttributes;

    /**
     * 路由缓存路径
     *
     * @var false|string
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
     * 设置缓存文件
     *
     * @param $cacheFile
     * @return $this
     */
    protected function setCacheFile($cacheFile)
    {
        if (!is_string($cacheFile) && $cacheFile !== false) {
            throw new InvalidArgumentException('Router cacheFile must be a string or false');
        }

        $this->cacheFile = $cacheFile;

        if ($cacheFile !== false && !is_writable(dirname($cacheFile))) {
            throw new RuntimeException('Router cacheFile directory must be writable');
        }

        return $this;
    }

    /**
     * 创建路由分组
     *
     * @param array $attributes
     * @param Closure $action
     */
    public function group(array $attributes, Closure $action)
    {
        $keyword = '/';

        if(isset($attributes['keyword'])){
            //关键词覆盖分组前缀
            $keyword = '/'.trim($attributes['keyword']);
            $attributes['prefix'] = $keyword;

            unset($attributes['keyword']);
        }

        $group = [$attributes,$action];

        //开启路由之后,再进行路由分组会直接生成路由映射
        if(!$this->routeStartEnable){
            $this->group[$keyword][] = $group;
        }else{
            $this->parseRoute([$group]);
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
        $this->addRoute('GET',$uri,$action);

        return $this;
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
        $this->addRoute('POST',$uri,$action);

        return $this;
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
        $this->addRoute('PUT',$uri,$action);

        return $this;
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
        $this->addRoute('DELETE',$uri,$action);

        return $this;
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
        $this->addRoute('HEAD',$uri,$action);

        return $this;
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
        $this->addRoute('PATCH',$uri,$action);

        return $this;
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
        $this->addRoute(['GET','POST','PUT','DELETE','HEAD','PATCH'],$uri,$action);

        return $this;
    }

    /**
     * 注册一个路由映射
     *
     * @param $method array|string   请求方式
     * @param $uri string            请求地址
     * @param $action string|Closure 映射地址(控制器的方法或者一个闭包函数)
     */
    public function addRoute($method,$uri,$action)
    {
        $action = $this->parseAction($action);

        if(isset($this->groupAttributes)){
            //加载分组属性
            if(isset($this->groupAttributes['prefix'])){
                $uri = trim($this->groupAttributes['prefix'],'/').rtrim($uri,'/');
            }

            if(isset($this->groupAttributes['suffix'])){
                $uri = trim($uri,'/').'/'.rtrim($this->groupAttributes['suffix'],'/');
            }

            $action = $this->mergeGroupNamespace(
                $this->mergeMiddlewareGroup($action)
            );
        }

        $uri = '/'.trim($uri,'/');

        foreach ((array) $method as $verb) {
            $this->routes[$verb.$uri] = ['method' => $verb, 'uri' => $uri, 'action' => $action];
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
     * 合并分组命名空间
     *
     * @param $action
     * @return mixed
     */
    protected function mergeGroupNamespace($action)
    {
        if(isset($action['uses']) && isset($this->groupAttributes['namespace'])){
            $action['uses'] = rtrim($this->groupAttributes['namespace'],'\\').'\\'.trim($action['uses'],'\\');
        }

        return $action;
    }

    /**
     * 合并分组中间件
     *
     * @param $action
     * @return mixed
     */
    protected function mergeMiddlewareGroup($action)
    {
        if (isset($this->groupAttributes['middleware'])) {
            if (isset($action['middleware'])) {
                $action['middleware'] = array_merge($this->groupAttributes['middleware'], $action['middleware']);
            } else {
                $action['middleware'] = $this->groupAttributes['middleware'];
            }
        }

        return $action;
    }

    /**
     * 调度传入的请求
     *
     * @param Http\Request $request
     * @return array
     */
    protected function dispatch(\Ant\Http\Request $request)
    {
        $this->addRouteFromGroup($request);

        if (isset($this->routes[$this->routeRequest])) {
            return $this->handleFoundRoute($this->routes[$this->routeRequest]['action'],[]);
        }

        return $this->handleDispatcher($this->createDispatcher()->dispatch(
            $request->getMethod(),
            $request->getRequestRoute()
        ));
    }

    /**
     * 添加分组路由
     *
     * @param $request \Ant\Http\Request
     */
    protected function addRouteFromGroup($request)
    {
        $method = $request->getMethod();
        $pathInfo = $request->getRequestRoute();
        $this->routeRequest = $method.$pathInfo;

        //获取关键词
        $keywords = array_keys(array_reverse($this->group));

        //进行关键词匹配
        foreach($keywords as $keyword){
            if(stripos($pathInfo,$keyword) === 0){
                $this->parseRoute($this->group[$keyword]);

                break;
            }
        }
    }

    /**
     * 解析路由
     *
     * @param array $group
     */
    protected function parseRoute(array $group)
    {
        foreach($group as list($attributes,$action)){
            //保留父级分组属性
            $parentGroupAttributes = $this->groupAttributes;

            if (isset($attributes['middleware']) && is_string($attributes['middleware'])) {
                $attributes['middleware'] = explode('|', $attributes['middleware']);
            }

            if(isset($attributes['cacheFile'])){
                $this->setCacheFile($attributes['cacheFile']);
            }

            $this->groupAttributes = $attributes;

            call_user_func($action, $this);

            $this->groupAttributes = $parentGroupAttributes;
        }
    }

    /**
     * 处理映射成功的路由
     *
     * @param $action
     * @param array $args
     * @return array
     */
    protected function handleFoundRoute($action,$args = [])
    {
        $handle = [];

        if(isset($action['middleware'])){
            $handle = $this->gatMiddleware($action['middleware']);
        }

        $handle[] = function(...$params)use($action,$args){
            $this->callAction($action,array_merge($args,$params));
        };

        return $handle;
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
     * 加载路由使用的中间件
     *
     * @param $middleware
     * @return array
     */
    protected function gatMiddleware($middleware)
    {
        $middleware = is_string($middleware) ? explode('|', $middleware) : (array) $middleware;

        return array_map(function($name){
            $middleware = isset($this->handlers[$name]) ? $this->handlers[$name] : $name;

            //获取可以回调的路由
            if(is_string($middleware)){
                return $this->container->make($middleware);
            }elseif($middleware instanceof Closure){
                return $middleware;
            }

            throw new InvalidArgumentException("Middleware [$middleware] does not exist");
        },$middleware);
    }

    /**
     * 调用基于数组的路由
     *
     * @param $action
     * @param array $args
     * @return mixed
     */
    protected function callAction($action,$args = [])
    {
        if(isset($action['uses'])){
            return $this->callController($action['uses'],$args);
        }

        foreach($action as $value){
            if($value instanceof Closure){
                return $this->container->call($value,$args);
            }
        }

        throw new BadFunctionCallException('Routing callback failed');
    }

    /**
     * 基于“控制器@方法”的方式调用
     *
     * @param $uses
     * @param array $args
     * @return mixed
     */
    protected function callController($uses,$args = [])
    {
        if (is_string($uses) && ! strpos($uses, '@') === false) {
            $uses .= '@__invoke';
        }

        list($controller, $method) = explode('@', $uses);

        if (!method_exists($instance = $this->container->make($controller), $method)) {
            throw new \Ant\Http\Exception(404);
        }

        return $this->container->call(
            [$instance, $method], $args
        );
    }

    /**
     * 获取路由调度器
     *
     * @return false|GroupCountBased
     */
    protected function createDispatcher()
    {
        if($this->dispatcher){
            return $this->dispatcher;
        }

        $dispatcherCallable = function(RouteCollector $r){
            //加载路由到路由控制器中
            foreach($this->routes as $route){
                $r->addRoute($route['method'],$route['uri'],$route['action']);
            }
        };

        //是否使用FastRoute路由缓存
        if($this->cacheFile){
            $this->dispatcher = \FastRoute\cachedDispatcher($dispatcherCallable,[
                'cacheFile' => $this->cacheFile,
                'cacheDisabled' => true,
            ]);
        }else{
            $this->dispatcher = \FastRoute\simpleDispatcher($dispatcherCallable);
        }

        return $this->dispatcher;
    }

    /**
     * 设置FastRoute调度器.
     *
     * @param  \FastRoute\Dispatcher  $dispatcher
     */
    public function setDispatcher(\FastRoute\Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * 添加中间件
     *
     * @param $handlers
     */
    public function addMiddleware($handlers)
    {
        if(!is_array($handlers)){
            $handlers = func_get_args();
        }

        $this->handlers = array_merge($this->handlers,$handlers);
    }

    /**
     * 设置异常处理
     *
     * @param callable $handle
     */
    public function setExceptionHandle(callable $handle)
    {
        $this->exceptionHandle = $handle;
    }

    /**
     * 获取异常处理
     *
     * @return callable|Closure|false
     */
    public function getExceptionHandle()
    {
        if($this->exceptionHandle){
            return $this->exceptionHandle;
        }

        return function($e){
            throw $e;
        };
    }

    /**
     * 执行路由中间件
     *
     * @param $request
     * @param $response
     */
    public function execute($request,$response)
    {
        try{
            //启动路由器
            $this->routeStartEnable = true;

            $handlers = $this->dispatch($request);

            $this->withArguments([$request,$response]);

            $this->executeMiddleware($handlers);
        }catch(Exception $exception){
            call_user_func($this->getExceptionHandle(),$exception,$request,$response);
        }catch(Throwable $error){
            call_user_func($this->getExceptionHandle(),$error,$request,$response);
        }finally{
            $this->routeStartEnable = false;
        }
    }
}