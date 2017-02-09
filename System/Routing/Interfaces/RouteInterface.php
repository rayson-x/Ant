<?php
namespace Ant\Routing\Interfaces;

interface RouteInterface
{
    /**
     * 获取路由请求方式
     *
     * @return string
     */
    public function getMethod();

    /**
     * 获取路由uri
     *
     * @return string
     */
    public function getUri();

    /**
     * 获取路由将要执行的动作
     *
     * @return callable|string
     */
    public function getAction();

    /**
     * 获取路由需要的中间件
     *
     * @return array
     */
    public function getMiddleware();

    /**
     * 替换现有中间件
     *
     * @param $middleware
     */
    public function setMiddleware($middleware);
    
    /**
     * 添加一个中间件
     *
     * @param $middleware
     */
    public function addMiddleware($middleware);

    /**
     * 重置中间件
     */
    public function resetMiddleware();

    /**
     * 获取一个默认路由参数
     *
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function getArgument($name, $default = null);

    /**
     * 获取所有默认路由参数
     *
     * @return array
     */
    public function getArguments();

    /**
     * 设置一个默认的路由参数
     *
     * @param $name
     * @param null $default
     */
    public function setArgument($name, $default = null);

    /**
     * 设置一组默认的路由参数
     *
     * @param array $arguments
     */
    public function setArguments(array $arguments);

    /**
     * 获取路由可以响应的类型
     *
     * @return array
     */
    public function getResponseType();

    /**
     * 设置路由响应类型
     *
     * @param array $responseType
     */
    public function setResponseType(array $responseType);

    /**
     * 添加一个路由响应类型
     *
     * @param $type
     */
    public function withAddResponseType($type);

}