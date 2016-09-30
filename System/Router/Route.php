<?php
namespace Ant\Router;

use Ant\Interfaces\Router\RouteInterface;

class Route implements RouteInterface
{
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
     * @var callable
     */
    protected $callback;

    /**
     * 路由需要的中间件
     *
     * @var array
     */
    protected $middleware;

    /**
     * 路由分组属性
     *
     * @var array
     */
    protected $groupAttributes;

    public function __construct($method,$uri,$action,array $groupAttributes = [])
    {
        $action = $this->parseAction($action);
        $this->groupAttributes = $groupAttributes;

        if(isset($groupAttributes)){
            //继承路由组信息
            if(isset($this->groupAttributes['prefix'])){
                $uri = trim($this->groupAttributes['prefix'],'/').'/'.trim($uri,'/');
            }

            if(isset($this->groupAttributes['suffix'])){
                $uri = trim($uri,'/').'/'.trim($this->groupAttributes['suffix'],'/');
            }

            $action = $this->mergeGroupNamespace(
                $this->mergeMiddlewareGroup($action)
            );
        }

        if(empty($action['use'])){
            foreach($action as $value){
                if($value instanceof \Closure){
                    $action['use'] = $value;
                    break;
                }
            }
        }

        $this->uri = '/'.trim($uri,'/');
        $this->method = $method;
        $this->callback = $action['use'];
        $this->middleware = $action['middleware'] ?: [];
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
                $action['middleware'] = array_merge($this->groupAttributes['middleware'], (array)$action['middleware']);
            } else {
                $action['middleware'] = $this->groupAttributes['middleware'];
            }
        }

        return $action;
    }

    /**
     * @return array
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 获取用户行为
     *
     * @return array
     */
    public function getCallable()
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
}