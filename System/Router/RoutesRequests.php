<?php
namespace Ant\Router;

use Closure;
use Ant\Middleware\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouterRequest
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
     * 优先加载路由
     *
     * @var array
     */
    protected $beforeRoute = [];

    /**
     * 默认加载路由
     *
     * @var array
     */
    protected $defaultRoute = [];

    /**
     * 最后加载路由
     *
     * @var array
     */
    protected $afterRoute = [];

    /**
     * 路由分组
     *
     * @var array
     */
    protected $group = [
        'before'    =>  [],
        'default'   =>  [],
        'after'     =>  [],
    ];

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

    public function group(array $attributes, Closure $callback,$level = 'default')
    {
        $group = compact('attributes','callback');

        $this->group[$level][] = $group;

        return $this;
    }

    public function withAddedBeforeGroup(array $attributes, Closure $callback)
    {
        $this->group($attributes,$callback,'before');
    }

    public function withAddedDefaultGroup(array $attributes, Closure $callback)
    {
        $this->group($attributes,$callback,'default');
    }

    public function withAddedAfterGroup(array $attributes, Closure $callback)
    {
        $this->group($attributes,$callback,'after');
    }

    protected function parseBeforeGroup()
    {
        $this->parseRoute($this->group['before']);
    }

    protected function parseDefaultGroup()
    {
        $this->parseRoute($this->group['default']);
    }

    protected function parseAfterGroup()
    {
        $this->parseRoute($this->group['after']);
    }

    protected function parseRoute(array $groups)
    {
        foreach($groups as $group){
            $parentGroupAttributes = $this->groupAttributes;

            $attributes = $group['attributes'];

            if (isset($attributes['middleware']) && is_string($attributes['middleware'])) {
                $attributes['middleware'] = explode('|', $attributes['middleware']);
            }

            $this->groupAttributes = $attributes;

            call_user_func($group['callable'], $this);

            $this->groupAttributes = $parentGroupAttributes;
        }
    }

    public function addRoute($method,$url,$callable)
    {

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