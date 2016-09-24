<?php
namespace Ant;

use Closure;
use Exception;
use Throwable;
use RuntimeException;
use InvalidArgumentException;
use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher\GroupCountBased;

//TODO::中间件加载过程
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
        '/' =>  [], //默认关键值
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

    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    public function setCacheFile($cacheFile)
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

    public function group(array $attributes, Closure $action)
    {
        $keyword = '/';

        if(isset($attributes['keyword'])){
            $keyword = '/'.trim($attributes['keyword']);
            $attributes['prefix'] = $keyword;

            unset($attributes['keyword']);
        }

        $this->group[$keyword][] = [$attributes,$action];
    }

    public function get($uri,$action)
    {
        $this->addRoute('GET',$uri,$action);
    }

    public function post($uri,$action)
    {
        $this->addRoute('POST',$uri,$action);
    }

    public function put($uri,$action)
    {
        $this->addRoute('PUT',$uri,$action);
    }

    public function delete($uri,$action)
    {
        $this->addRoute('DELETE',$uri,$action);
    }

    public function head($uri,$action)
    {
        $this->addRoute('HEAD',$uri,$action);
    }

    public function patch($uri,$action)
    {
        $this->addRoute('PATCH',$uri,$action);
    }

    public function addRoute($method,$uri,$action)
    {
        $action = $this->parseAction($action);

        if(isset($this->groupAttributes)){
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

    protected function mergeGroupNamespace($action)
    {
        if(isset($action['uses']) && isset($this->groupAttributes['namespace'])){
            $action['uses'] = rtrim($this->groupAttributes['namespace'],'\\').'\\'.trim($action['uses'],'\\');
        }

        return $action;
    }

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

    protected function handleFoundRoute($action,$args = [])
    {
        $handle = [];

        if(isset($action['middleware'])){
            $handle = $this->getMiddleware($action['middleware']);
        }

        $handle[] = function()use($action,$args){
            $this->callAction($action,$args);
        };

        return $handle;
    }

    protected function getMiddleware($middleware)
    {
        $middleware = is_string($middleware) ? explode('|', $middleware) : (array) $middleware;

        return array_map(function($name){
            $middleware = isset($this->handlers[$name]) ? $this->handlers[$name] : $name;

            if(is_string($middleware)){
                return $this->container->make($middleware);
            }elseif($middleware instanceof Closure){
                return $middleware;
            }
            //TODO::异常
        },$middleware);
    }

    protected function callAction($action,$args = [])
    {
        if(isset($action['uses'])){
            return $this->callController($action['uses'],$args);
        }

        foreach($action as $value){
            if($value instanceof Closure){
                $closure = $value;
                break;
            }
        }

        return $this->container->call($closure,$args);
    }

    protected function callController($uses,$args = [])
    {
        if (is_string($uses) && ! strpos($uses, '@') === false) {
            $uses .= '@__invoke';
        }

        list($controller, $method) = explode('@', $uses);

        if (! method_exists($instance = $this->container->make($controller), $method)) {
            throw new \Ant\Http\Exception(404);
        }

        return $this->container->call(
            [$instance, $method], $args
        );
    }

    protected function handleDispatcher($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new \Ant\Http\Exception(404);

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new \Ant\Http\Exception(405);

            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo[1],$routeInfo[2]);
        }
    }

    protected function dispatch(RequestInterface $request)
    {
        $this->addRouteFromGroup($request);

        if (isset($this->routes[$this->routeRequest])) {
            return $this->handleFoundRoute($this->routes[$this->routeRequest]['action'],[]);
        }

        return $this->handleDispatcher($this->createDispatcher()->dispatch(
            $request->getMethod(),
            $request->getAttribute('virtualPath')
        ));
    }

    protected function addRouteFromGroup($request)
    {
        $method = $request->getMethod();
        $pathInfo = $request->getAttribute('virtualPath');
        $this->routeRequest = $method.$pathInfo;

        $default = array_shift($this->group);

        $keywords = array_keys($this->group);

        foreach($keywords as $keyword){
            if(stripos($keyword,$pathInfo) === 0){
                $this->parseRoute($this->group[$keyword]);

                return;
            }
        }

        $this->parseRoute($default);
    }

    /**
     * 解析路由
     *
     * @param array $group
     */
    protected function parseRoute(array $group)
    {
        foreach($group as list($attributes,$action)){
            $parentGroupAttributes = $this->groupAttributes;

            if (isset($attributes['middleware']) && is_string($attributes['middleware'])) {
                $attributes['middleware'] = explode('|', $attributes['middleware']);
            }

            $this->groupAttributes = $attributes;

            call_user_func($action, $this);

            $this->groupAttributes = $parentGroupAttributes;
        }
    }

    /**
     * @return false|GroupCountBased
     */
    public function createDispatcher()
    {
        if($this->dispatcher){
            return $this->dispatcher;
        }

        $dispatcherCallable = function(RouteCollector $r){
            foreach($this->routes as $route){
                $r->addRoute($route['method'],$route['uri'],$route['action']);
            }
        };

        if($this->cacheFile){
            $this->dispatcher = \FastRoute\cachedDispatcher($dispatcherCallable,[
                'cacheFile' => $this->cacheFile,
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

    public function addMiddleware($handlers)
    {
        if(!is_array($handlers)){
            $handlers = func_get_args();
        }

        $this->handlers = array_merge($this->handlers,$handlers);
    }

    public function execute($request,$response)
    {
        $handlers = $this->dispatch($request);

        $this->executeMiddleware($handlers,[$request,$response]);

        try{
        }catch(Exception $exception){

        }catch(Throwable $error){

        }
    }
}