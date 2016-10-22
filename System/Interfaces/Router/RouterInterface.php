<?php
namespace Ant\Interfaces\Router;

/**
 * 路由器接口类
 *
 * Interface RouterInterface
 * @package Ant\Interfaces\Router
 */
Interface RouterInterface
{
    /**
     * 创建一组路由,共用路由属性
     *
     * @param array $attributes
     * @param \Closure $action
     * @return mixed
     */
    public function group(array $attributes,\Closure $action);

    /**
     * 创建一条路由映射
     *
     * @param $method
     * @param $uri
     * @param $action
     * @return mixed
     */
    public function map($method,$uri,$action);

    /**
     * 路由分发
     *
     * @param $request
     * @return mixed
     */
    public function dispatch($request);
}