<?php
namespace Ant\Router;

use Closure;
use Ant\Middleware\Middleware;
use Ant\Interfaces\RouterInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use FastRoute\Dispatcher\GroupCountBased;

//TODO::中间件加载过程
//TODO::通过反射生成路由缓存 需要Console支持
//TODO::路由缓存
//TODO;:采用惰性加载,不在创建时生成,在使用时生成
//TODO::每一个分组遵循自己的规则,如中间件,前缀,命名空间等
//TODO::为了减少空匹配,可以将一组路由与一个关键词绑定,路由器仅匹配该组中的路由
class RouterRequest implements RouterInterface
{
    use Middleware{
        Middleware::execute as executeMiddleware();
    }

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
     * @var string
     */
    protected $dispatcher = null;

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
    protected $groupAttributes;


    public function addMiddleware($handlers)
    {
        if(!is_array($handlers)){
            $handlers = func_get_args();
        }

        $this->handlers = array_merge($this->handlers,$handlers);
    }

    public function group(array $attributes, Closure $callback)
    {
        $parentGroupAttributes = $this->groupAttributes;

        if (isset($attributes['middleware']) && is_string($attributes['middleware'])) {
            $attributes['middleware'] = explode('|', $attributes['middleware']);
        }

        $this->groupAttributes = $attributes;

        call_user_func($callback, $this);

        $this->groupAttributes = $parentGroupAttributes;
    }

    public function addRoute($method,$url,$callable)
    {

    }

    public function dispatch(RequestInterface $request)
    {

    }

    public function createDispatcher()
    {
        return $this->dispatcher ?: \FastRoute\simpleDispatcher(function($r){
            /* @var $r \FastRoute\RouteCollector */
            foreach($this->routes as list($method,$path,$handle)){
                $r->addRoute($method,$path,$handle);
            }
        });
    }

    public function setDispatcher(GroupCountBased $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function execute(RequestInterface $request,ResponseInterface $response)
    {
        $this->routeRequest = $request->getMethod().$request->getRequestTarget();

//        try{
//            $this->executeMiddleware();
//        }catch(\Exception $exception){
//
//        }catch(\Throwable $error){
//
//        }
    }
}