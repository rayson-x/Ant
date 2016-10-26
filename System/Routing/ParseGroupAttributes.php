<?php
namespace Ant\Routing;


trait ParseGroupAttributes
{
    /**
     * 路由分组属性
     *
     * @var array
     */
    protected $groupAttributes = [];
    
    /**
     * 合并请求资源前缀与后缀
     *
     * @param $uri
     * @return string
     */
    protected function mergeGroupPrefixAndSuffix($uri)
    {
        if(isset($this->groupAttributes['prefix'])){
            $uri = trim($this->groupAttributes['prefix'],'/').'/'.trim($uri,'/');
        }

        if(isset($this->groupAttributes['suffix'])){
            $uri = trim($uri,'/').'/'.trim($this->groupAttributes['suffix'],'/');
        }

        return $uri;
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
}